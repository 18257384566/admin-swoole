package manager

import (
	"app/proto"
	"app/router"
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"

	"app/etcd"

	"github.com/julienschmidt/httprouter"
	"golang.org/x/net/context"
)

func init() {
	router.POST("/manager/ban", ban)
}

func ban(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	if !checkAuth(sess, "ban") {
		SendErr(w, 255, "Not obtained permission")
		return
	}
	errs := ""
	r.ParseForm()
	zone := r.FormValue("zones")
	if zone == "" {
		SendErr(w, 16, "zones could not empty")
		return
	}

	user := r.FormValue("user")
	if user == "" {
		SendErr(w, 17, "user could not empty")
		return
	}

	t, err := strconv.ParseInt(r.FormValue("t"), 10, 64)
	if err != nil {
		SendErr(w, 18, "Time is error")
		return
	}

	var ban struct {
		NickName  string
		TimeStamp int64
	}
	ban.NickName = user
	ban.TimeStamp = t

	message, err := json.Marshal(ban)
	if err != nil {
		SendErr(w, 19, err.Error())
		return
	}

	data := &proto.Center_Req{Type: 6, Message: message}
	if conns := etcd.GetGameAllConn(zone); conns != nil {
		for k, conn := range conns {
			cli := proto.NewGameServiceClient(conn)
			if reply, err := cli.Manage(context.Background(), data); err != nil {
				errs += fmt.Sprintf("server-zone%s ban error，index:%d，err:%s\n", zone, k, err.Error())
			} else {
				if len(reply.Message) == 2 {
					//发送成功，找到了用户登录的game，不需要继续发送到别的game上
					break
				}
			}
		}
	} else {
		errs += fmt.Sprintf("server-zone%s's connection did not exist\n", zone)
	}

	if errs == "" {
		SendSuccess(w, nil)
	} else {
		SendErr(w, 17, errs)
	}
}
