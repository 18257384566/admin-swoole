package etcd

import (
	"context"
	"encoding/json"
	"io/ioutil"
	"log"
	"os"
	"regexp"
	"strings"
	"sync"
	"time"

	etcdclient "github.com/coreos/etcd/client"
	"google.golang.org/grpc"
)

var warshipService *services

const (
	DEFAULT_ETCD         = "http://127.0.0.1:2379"
	DEFAULT_SERVICE_PATH = "/backends"
	SERVERLISTFILE       = "config/ServerInfo/serverlist.html"
)

type grpcclientconn struct {
	key  []string
	conn []*grpc.ClientConn
}

type service struct {
	ServerName   string   `json:"N"`           //服务名称
	ServerAddr   []string `json:"A,omitempty"` //服务地址和端口
	ServerStatus uint8    `json:"S,omitempty"` //服务状态
}

type services struct {
	Version        uint16                    `json:"V"`           //服务器版本
	Agent          map[string]service        `json:"A,omitempty"` //启动好的区服入口
	Game           map[string]grpcclientconn `json:"-"`           //提供服务的游戏逻辑服
	code           grpcclientconn            //兑换码服务器
	replaysManager grpcclientconn            //录像墙服务器
	ReplaysNode    []string                  `json:"R,omitempty"` //录像墙节点
	client         etcdclient.Client
	zoneMap        map[string]string //zone映射表
	sync.RWMutex   `json:"-"`
}

func (this *services) init() {
	this.Agent = make(map[string]service)
	this.Game = make(map[string]grpcclientconn)
	this.zoneMap = make(map[string]string)
	// etcd connect
	machines := []string{DEFAULT_ETCD}
	if env := os.Getenv("ETCD_HOST"); env != "" {
		machines = strings.Split(env, ";")
	}

	// init etcd client
	cfg := etcdclient.Config{
		Endpoints: machines,
		Transport: etcdclient.DefaultTransport,
	}
	c, err := etcdclient.New(cfg)
	if err != nil {
		log.Panic(err)
		os.Exit(-1)
	}
	this.client = c

	//读取区服列表信息
	if _, err := os.Stat(SERVERLISTFILE); err == nil {
		b, err := ioutil.ReadFile(SERVERLISTFILE)
		if err != nil {
			panic(err)
		}

		err = json.Unmarshal(b, this)
		if err != nil {
			panic(err)
		}
		//把所有区服状态置为维护状态，清理掉IP信息
		for k, v := range this.Agent {
			v.ServerAddr = nil
			v.ServerStatus = 0
			this.Agent[k] = v
		}

		//移除所有的录像墙节点，待后续自动生成
		this.ReplaysNode = nil
	}

	//读取etcd,生成开放的区服
	kAPI := etcdclient.NewKeysAPI(this.client)

	var i int
	for {
		resp, err := kAPI.Get(context.Background(), DEFAULT_SERVICE_PATH, &etcdclient.GetOptions{Recursive: true})
		if err != nil {
			if i < 3 {
				i++
				log.Panicln(err, "retry", i)
				time.Sleep(2 * time.Second)
				continue
			}
			panic(err)
		}

		if resp.Node.Dir {
			for _, node := range resp.Node.Nodes {
				if node.Dir && strings.Contains(node.Key, "agent-zone") { // service directory
					for _, v := range node.Nodes {
						this.add_agent(v.Key, v.Value)
					}
				}

				if node.Dir && strings.Contains(node.Key, "game-zone") { // service directory
					for _, v := range node.Nodes {
						this.add_game(v.Key, v.Value)
					}
				}

				//添加code服务器
				if node.Dir && strings.Contains(node.Key, "codeServer") { // service directory
					for _, v := range node.Nodes {
						this.add_codeServer(v.Key, v.Value)
					}
				}

				//添加replaysManager服务器
				if node.Dir && strings.Contains(node.Key, "replaysManager") { // service directory
					for _, v := range node.Nodes {
						this.add_replaysManager(v.Key, v.Value)
					}
				}

				//添加replaysNode服务器
				if node.Dir && strings.Contains(node.Key, "replaysNode") { // service directory
					for _, v := range node.Nodes {
						this.add_replaysNode(v.Key, v.Value)
					}
				}
			}
		}
		break
	}

	//生成区服列表文件
	this.generate()
	//开启观察者
	go this.wacther()
}

func (this *services) generate() {
	if b, err := json.Marshal(this); err != nil {
		panic(err)
	} else {
		err = ioutil.WriteFile(SERVERLISTFILE, b, 0644)
		if err != nil {
			panic(err)
		}
		log.Println("generate serverlist.html:", string(b))
	}
}

func (this *services) wacther() {
	kAPI := etcdclient.NewKeysAPI(this.client)
	w := kAPI.Watcher(DEFAULT_SERVICE_PATH, &etcdclient.WatcherOptions{Recursive: true})
	for {
		resp, err := w.Next(context.Background())

		if err != nil {
			log.Println(err)
			continue
		}
		if resp.Node.Dir {
			continue
		}

		switch resp.Action {
		case "set", "create", "update", "compareAndSwap":
			this.add_service(resp.Node.Key, resp.Node.Value)
		case "delete":
			this.del_service(resp.PrevNode.Key, resp.PrevNode.Value)
		}
	}
}

func (this *services) add_service(key, value string) {
	this.Lock()
	defer this.Unlock()

	//网关添加
	if strings.Contains(key, "agent-zone") {
		this.add_agent(key, value)
		this.generate()
	}
	//Game添加
	if strings.Contains(key, "game-zone") {
		this.add_game(key, value)
	}
	//codeServer添加
	if strings.Contains(key, "codeServer") {
		this.add_codeServer(key, value)
	}
	//replaysManager添加
	if strings.Contains(key, "replaysManager") {
		this.add_replaysManager(key, value)
	}
	//replaysNode添加
	if strings.Contains(key, "replaysNode") {
		this.add_replaysNode(key, value)
		this.generate()
	}
}

func (this *services) del_service(key string, value string) {
	this.Lock()
	defer this.Unlock()

	//网关删除
	if strings.Contains(key, "agent-zone") {
		this.del_agent(key, value)
		this.generate()
	}
	//Game删除
	if strings.Contains(key, "game-zone") {
		this.del_game(key, value)
	}
	//codeServer删除
	if strings.Contains(key, "codeServer") {
		this.del_codeServer(key, value)
	}
	//replaysManager删除
	if strings.Contains(key, "replaysManager") {
		this.del_replaysManager(key, value)
	}
	//replaysNode删除
	if strings.Contains(key, "replaysNode") {
		this.del_replaysNode(key, value)
		this.generate()
	}
}

func (this *services) add_agent(key string, value string) {

	re := regexp.MustCompile(`agent-zone(\d+)`)
	match := re.FindStringSubmatch(key)

	if match != nil {
		temp := strings.SplitN(key, ":", 2)
		if len(temp) == 2 {
			server := strings.Split(temp[1], "&")
			for _, tempServer := range server {
				tempServerData := strings.Split(tempServer, "-")
				if len(tempServerData) != 2 {
					continue
				}

				s := this.Agent[tempServerData[1]]
				s.ServerName = tempServerData[0]
				s.ServerAddr = append(s.ServerAddr, value)
				s.ServerStatus = 1
				this.Agent[tempServerData[1]] = s

				//生成zone的映射表
				this.zoneMap[tempServerData[1]] = match[1]
				log.Println("agent added:", s.ServerName, value)
			}
		}
	} else {
		log.Println(`add_agent match agent-zone(\d+) err`, key, "-->", value)
	}
}

func (this *services) del_agent(key string, value string) {
	re := regexp.MustCompile(`agent-zone(\d+)`)
	match := re.FindStringSubmatch(key)

	if match != nil {
		temp := strings.SplitN(key, ":", 2)
		if len(temp) == 2 {
			server := strings.Split(temp[1], "&")
			for _, tempServer := range server {
				tempServerData := strings.Split(tempServer, "-")
				if len(tempServerData) != 2 {
					continue
				}

				if s, ok := this.Agent[tempServerData[1]]; ok {
					for k, v := range s.ServerAddr {
						if v == value {
							s.ServerAddr = append(s.ServerAddr[:k], s.ServerAddr[k+1:]...)
							if len(s.ServerAddr) == 0 {
								s.ServerStatus = 0
								//删除对应的zone的映射表
								for k, v := range this.zoneMap {
									if v == match[1] {
										delete(this.zoneMap, k)
									}
								}
							}
							this.Agent[tempServerData[1]] = s
							log.Println("agent delete:", s.ServerName, value)
							break
						}
					}
				}
			}
		}
	} else {
		log.Println(`del_agent match agent-zone(\d+) err`, key, "-->", value)
	}
}

func (this *services) add_game(key string, value string) {
	re := regexp.MustCompile(`game-zone(\d+)`)
	match := re.FindStringSubmatch(key)
	if match != nil {
		if conn, err := grpc.Dial(value, grpc.WithBlock(), grpc.WithInsecure(), grpc.WithTimeout(20*time.Second)); err == nil {
			temp := this.Game[match[1]]
			temp.key = append(temp.key, value)
			temp.conn = append(temp.conn, conn)
			this.Game[match[1]] = temp
			log.Println("game added:", key, "-->", value)
		} else {
			log.Println("did not connect:", key, "-->", value, "error:", err)
		}
	} else {
		log.Println(`add_game match game-zone(\d+) err`, key, "-->", value)
	}
}

func (this *services) del_game(key string, value string) {
	re := regexp.MustCompile(`game-zone(\d+)`)
	match := re.FindStringSubmatch(key)
	if match != nil {
		if temp, ok := this.Game[match[1]]; ok {
			for k, v := range temp.key {
				if v == value {
					temp.conn[k].Close()
					temp.key = append(temp.key[:k], temp.key[k+1:]...)
					temp.conn = append(temp.conn[:k], temp.conn[k+1:]...)
					if len(temp.conn) == 0 {
						delete(this.Game, match[1])
					} else {
						this.Game[match[1]] = temp
					}
					log.Println(`game deleted: `, key, "-->", value)
					return
				}
			}
		}
	} else {
		log.Println(`del_game match game-zone(\d+) err`, key, "-->", value)
	}
}

func (this *services) add_codeServer(key string, value string) {
	if conn, err := grpc.Dial(value, grpc.WithBlock(), grpc.WithInsecure(), grpc.WithTimeout(20*time.Second)); err == nil {
		this.code.key = append(this.code.key, key)
		this.code.conn = append(this.code.conn, conn)
		log.Println("codeServer added:", key, "-->", value)
	} else {
		log.Println("did not connect:", key, "-->", value, "error:", err)
	}
}

func (this *services) del_codeServer(key string, value string) {
	for k, v := range this.code.key {
		if v == key {
			this.code.conn[k].Close()
			this.code.key = append(this.code.key[:k], this.code.key[k+1:]...)
			this.code.conn = append(this.code.conn[:k], this.code.conn[k+1:]...)
			log.Println(`codeServer deleted: `, key, "-->", value)
			return
		}
	}
}

func (this *services) add_replaysManager(key string, value string) {
	if conn, err := grpc.Dial(value, grpc.WithBlock(), grpc.WithInsecure(), grpc.WithTimeout(20*time.Second)); err == nil {
		this.replaysManager.key = append(this.replaysManager.key, key)
		this.replaysManager.conn = append(this.replaysManager.conn, conn)
		log.Println("replaysManager added:", key, "-->", value)
	} else {
		log.Println("did not connect:", key, "-->", value, "error:", err)
	}
}

func (this *services) del_replaysManager(key string, value string) {
	for k, v := range this.replaysManager.key {
		if v == key {
			this.replaysManager.conn[k].Close()
			this.replaysManager.key = append(this.replaysManager.key[:k], this.replaysManager.key[k+1:]...)
			this.replaysManager.conn = append(this.replaysManager.conn[:k], this.replaysManager.conn[k+1:]...)
			log.Println(`replaysManager deleted: `, key, "-->", value)
			return
		}
	}
}

func (this *services) add_replaysNode(key string, value string) {
	this.ReplaysNode = append(this.ReplaysNode, value)
	log.Println("replaysNode added:", key, "-->", value)
}

func (this *services) del_replaysNode(key string, value string) {
	for k, v := range this.ReplaysNode {
		if v == value {
			this.ReplaysNode = append(this.ReplaysNode[:k], this.ReplaysNode[k+1:]...)
			log.Println(`replaysNode deleted: `, key, "-->", value)
			return
		}
	}
}

func (this *services) update_version() uint16 {
	this.Lock()
	defer this.Unlock()
	this.Version += 1
	this.generate()
	return this.Version
}

func init() { //读取文件版本
	warshipService = &services{}
	warshipService.init()
}

func UpdateVer() uint16 {
	return warshipService.update_version()
}

func GetGameOneConn(zone string) *grpc.ClientConn {
	if grpcclient, ok := warshipService.Game[zone]; ok {
		if len(grpcclient.conn) > 0 {
			return grpcclient.conn[0]
		}
	}

	return nil
}

func GetGameAllConn(zone string) []*grpc.ClientConn {
	if grpcclient, ok := warshipService.Game[zone]; ok {
		if len(grpcclient.conn) > 0 {
			return grpcclient.conn
		}
	}

	return nil
}

func GetAllRealZone() map[string]string {
	return warshipService.zoneMap
}

func GetRealZone(zone string) string {
	return warshipService.zoneMap[zone]
}

func FilterRepeatedZones(zones []string) (distinctZones []string) {

	noRepeated := make(map[string]struct{})
	for _, zone := range zones {
		if z, ok := warshipService.zoneMap[zone]; ok {
			noRepeated[z] = struct{}{}
		}
	}

	for zone, _ := range noRepeated {
		distinctZones = append(distinctZones, zone)
	}

	return
}

func GetCodeServerConn() *grpc.ClientConn {
	if len(warshipService.code.conn) > 0 {
		return warshipService.code.conn[0]
	}
	return nil
}

func GetReplaysManagerConn() *grpc.ClientConn {
	if len(warshipService.replaysManager.conn) > 0 {
		return warshipService.replaysManager.conn[0]
	}
	return nil
}
