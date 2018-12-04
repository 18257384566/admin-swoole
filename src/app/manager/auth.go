package manager

import (
	"app/router"
	"net/http"
	"os"
	"path"
	"regexp"
	"strings"

	"github.com/Unknwon/goconfig"
	"github.com/astaxie/beego/session"
	"github.com/julienschmidt/httprouter"
)

const (
	USERDBFILE = "config/Db/db.ini"
)

var globalSessions *session.Manager
var dbini *goconfig.ConfigFile

func init() {
	router.GET("/gm/login/:username/:password", Login)
	router.GET("/gm/getuserlist", GetUserList)
	router.GET("/gm/setauth/:username/*auth", SetAuth)
	router.GET("/gm/adduser/:username/:password", AddUser)
	router.GET("/gm/deluser/:username", DelUser)

	cf := &session.ManagerConfig{CookieName: "sessionid", EnableSetCookie: true, Gclifetime: 3600, Maxlifetime: 3600, Secure: false, CookieLifeTime: 3600}
	globalSessions, _ = session.NewManager("memory", cf)
	go globalSessions.GC()

	//初始化用户数据ini文件
	if _, err := os.Stat(USERDBFILE); err != nil {
		//创建文件
		if err := os.MkdirAll(path.Dir(USERDBFILE), 0755); err != nil {
			panic(err)
		}
		f, err := os.Create(USERDBFILE)
		if err != nil {
			panic(err)
		}
		f.Close()
	}

	var err error
	dbini, err = goconfig.LoadConfigFile(USERDBFILE)
	if err != nil {
		panic(err)
	}
}

func Login(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {
	w.Header().Set("Access-Control-Allow-Origin", "*")
	username := ps.ByName("username")
	passwrod := ps.ByName("password")

	if username == "" || passwrod == "" {
		SendErr(w, 6, "username or passwrod is empty")
		return
	}
	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	section, err := dbini.GetSection(username)
	if err != nil {
		SendErr(w, 7, "username or password is error")
		return
	}

	if section["password"] != passwrod {
		SendErr(w, 8, "username or password is error")
		return
	}
	sess.Set("login", true)
	sess.Set("auth", strings.Split(section["auth"], "|"))

	message := make(Message)
	message["sessid"] = sess.SessionID()
	message["auth"] = section["auth"]
	SendSuccess(w, message)
}

func checkAuth(sess session.Store, auth string) bool {
	status := sess.Get("login")
	if status == nil {
		return false
	}

	auths, ok := sess.Get("auth").([]string)
	if !ok {
		return false
	}

	for _, v := range auths {
		if v == "super" || v == auth {
			return true
		}
	}

	return false
}

func GetUserList(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	if !checkAuth(sess, "super") {
		SendErr(w, 255, "Not obtained permission")
		return
	}

	message := make(Message)

	list := dbini.GetSectionList()

	if list != nil {
	Label:
		for _, v := range list {
			section, _ := dbini.GetSection(v)
			auth := section["auth"]
			auths := strings.Split(auth, "|")
			for _, vv := range auths {
				if vv == "super" {
					continue Label
				}
			}
			message[v] = auth
		}
	}

	SendSuccess(w, message)
}

func SetAuth(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {

	username := ps.ByName("username")
	auth := ps.ByName("auth")

	if username == "" {
		SendErr(w, 6, "username is empty")
		return
	}

	auth = strings.TrimLeft(auth, "/")

	if auth != "" {
		reg := regexp.MustCompile(`^[\w|]+$`)
		if !reg.MatchString(auth) {
			SendErr(w, 15, "auth is wrong")
			return
		}
	}

	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	if !checkAuth(sess, "super") {
		SendErr(w, 255, "Not obtained permission")
		return
	}

	section, _ := dbini.GetSection(username)
	if section == nil {
		SendErr(w, 12, "username didn't exist")
		return
	}

	oldauths := strings.Split(section["auth"], "|")

	for _, v := range oldauths {
		if v == "super" {
			SendErr(w, 14, "not allowed")
			return
		}
	}

	auths := strings.Split(auth, "|")
	for _, v := range auths {
		if v == "super" {
			SendErr(w, 13, "auth is wrong")
			return
		}
	}
	dbini.SetValue(username, "auth", auth)
	if err := goconfig.SaveConfigFile(dbini, USERDBFILE); err != nil {
		SendErr(w, 11, err.Error())
		return
	}

	SendSuccess(w, nil)
}

func AddUser(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {

	username := ps.ByName("username")
	passwrod := ps.ByName("password")

	if username == "" || passwrod == "" {
		SendErr(w, 6, "username or passwrod is empty")
		return
	}

	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	if !checkAuth(sess, "super") {
		SendErr(w, 255, "Not obtained permission")
		return
	}

	section, _ := dbini.GetSection(username)
	if section != nil {
		SendErr(w, 12, "username exist")
		return
	}

	dbini.SetValue(username, "password", passwrod)
	dbini.SetValue(username, "auth", "")

	if err := goconfig.SaveConfigFile(dbini, USERDBFILE); err != nil {
		SendErr(w, 11, err.Error())
		return
	}

	SendSuccess(w, nil)
}

func DelUser(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {

	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	if !checkAuth(sess, "super") {
		SendErr(w, 255, "Not obtained permission")
		return
	}

	username := ps.ByName("username")
	if !dbini.DeleteSection(username) {
		SendErr(w, 9, "username didn't exist")
		return
	}
	if err := goconfig.SaveConfigFile(dbini, USERDBFILE); err != nil {
		SendErr(w, 10, err.Error())
		return
	}
	SendSuccess(w, nil)
}
