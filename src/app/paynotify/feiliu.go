package paynotify

import (
	"app/router"
	"crypto/md5"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"sort"
	"strconv"
	"strings"

	"github.com/julienschmidt/httprouter"
)

func init() {
	router.POST("/notify/feiliu/ios", feiliuios)
	router.POST("/notify/feiliu/android", feiliuandroid)
}

const (
	FEILIU_SUCCESS           = `{"code":"0","tips":"success"}`
	FEILIU_FAILED            = `{"code":"1","%s"}`
	FEILIU_IOS_APPSECRET     = `123`
	FEILIU_ANDROID_APPSECRET = `2270460d832c941d4c0fa1278611b58f`
)

type feiliufeild struct {
	k string
	v string
}

type feiliupost struct {
	PlatformOrderId string `json:"flOrderId"`
	GameOrderId     string `json:"cpOrderId"`
	AppId           string `json:"appId"`
	UserId          string `json:"userId"`
	GoodsId         string `json:"goodsId"`
	RoleId          string `json:"roleId"`
	GroupId         string `json:"groupId"`
	Amount          string `json:"amount"`
	Zone            string `json:"merPriv"`
	Status          string `json:"status"`
	Sign            string `json:"sign"`
}

func feiliuandroid(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	log.Println("收到飞流Android订单通知，开始校验->")
	notify := &Notify{}
	notify.Platform = PLATFORM_FEILIU
	notify.Channel = CHANNEL_FEILIU
	notify.System = SYSTEM_ANDROID
	feiliu_check(w, r, notify, FEILIU_IOS_APPSECRET)
}

func feiliuios(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	log.Println("收到飞流Ios订单通知，开始校验->")
	notify := &Notify{}
	notify.Platform = PLATFORM_FEILIU
	notify.Channel = CHANNEL_FEILIU
	notify.System = SYSTEM_IOS
	feiliu_check(w, r, notify, FEILIU_IOS_APPSECRET)
}

func feiliu_check(w http.ResponseWriter, r *http.Request, notify *Notify, secret string) {

	post := feiliupost{}

	//接收post原始数据
	raw, err := ioutil.ReadAll(r.Body)
	if err != nil {
		goto Err
	}

	//解码raw
	err = json.Unmarshal(raw, &post)
	if err != nil {
		goto Err
	}

	//验证Sign
	err = feiliu_sign_check(post, secret)
	if err != nil {
		goto Err
	}

	//验证Status
	if post.Status != "0" {
		log.Println("飞流失败订单，无需校验->", string(raw))
	} else {
		//构建消息，投递到GAME
		notify.PlatformOrderId = post.PlatformOrderId
		if gameOrderId, err := strconv.ParseUint(post.GameOrderId, 10, 64); err != nil {
			log.Println("错误：飞流订单校验GameOrderId错误->", string(raw))
		} else {
			notify.GameOrderId = gameOrderId
			amount, err := strconv.ParseUint(post.GameOrderId, 10, 64)
			if err != nil {
				log.Println("错误：飞流订单校验Amount错误->", string(raw))
			} else {
				notify.Amount = amount
				log.Println("成功：飞流订单校验->", string(raw))
				err = NotifyToGameServer(post.Zone, notify)
				if err != nil {
					goto Err
				}
			}
		}
	}

	fmt.Fprint(w, FEILIU_SUCCESS)
	return
Err:
	log.Println("失败：飞流订单校验->", err)
	fmt.Fprintf(w, FEILIU_FAILED, err)
}

func feiliu_sign_check(post feiliupost, secret string) error {

	feildslice := make([]feiliufeild, 0, 10)
	feildslice = append(feildslice, feiliufeild{"flOrderId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"cpOrderId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"appId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"userId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"goodsId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"roleId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"groupId", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"amount", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"merPriv", post.PlatformOrderId})
	feildslice = append(feildslice, feiliufeild{"status", post.PlatformOrderId})

	sort.Slice(feildslice, func(i, j int) bool {
		return feildslice[i].k <= feildslice[j].v
	})

	s := make([]string, 0, 10)
	for _, f := range feildslice {
		s = append(s, f.k+"="+f.v)
	}

	z := strings.Join(s, "&")
	z += secret

	hasher := md5.New()
	hasher.Write([]byte(z))
	if post.Sign != hex.EncodeToString(hasher.Sum(nil)) {
		return errors.New("sign validation failed")
	}

	return nil
}
