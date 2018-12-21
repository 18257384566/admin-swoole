package manager

import (
	"app/analytics"
	"app/router"
	// "fmt"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/julienschmidt/httprouter"
)

func init() {
	router.POST("/manager/analyze", Analyze)
}

func Analyze(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	w.Header().Set("Access-Control-Allow-Origin", "*")
	sess, _ := globalSessions.SessionStart(w, r)
	defer sess.SessionRelease(w)

	// if !checkAuth(sess, "analysis") {
	// 	SendErr(w, 255, "Not obtained permission")
	// 	return
	// }

	var data interface{}
	var err error

	r.ParseForm()
	timezone := time.UTC
	timeZone := r.FormValue("timezone")
	if timeZone != "" {
		timezone, err = time.LoadLocation(timeZone)
		if err != nil {
			SendErr(w, 18, err.Error())
			return
		}
	}

	t1, err := strconv.ParseInt(r.FormValue("t1"), 10, 64)
	if err != nil {
		SendErr(w, 18, "Time1 is error")
		return
	}
	t2, err := strconv.ParseInt(r.FormValue("t2"), 10, 64)
	if err != nil {
		SendErr(w, 18, "Time2 is error")
		return
	}

	time1 := time.Unix(t1/1000, 0).UTC()
	time2 := time.Unix(t2/1000, 0).UTC()

	if time2.After(time.Now().UTC()) {
		time2 = time.Now().UTC()
		t2 = time2.Unix() * 1000
	}

	if time1.After(time2) {
		SendErr(w, 18, "Time is error,start time must lower than end time")
		return
	}

	if time2.Sub(time1).Hours() > 8784 { //366*24
		SendErr(w, 18, "Time is wrong,can't search over one year")
		return
	}

	channels := r.FormValue("channels")
	channel := strings.Split(channels, "|")
	if len(channel) == 0 {
		SendErr(w, 19, "channel could not empty")
		return
	}

	zones := r.FormValue("zones")
	zone := strings.Split(zones, "|")
	if len(zone) == 0 {
		SendErr(w, 20, "zone could not empty")
		return
	}

	switch r.FormValue("type") {

	case "DailyPlayTime": //每日时长
		data, err = analytics.F_DailyPlayTime(t1, t2, zone, channel, timezone)
	case "DailyLogin": //每日登录数
		data, err = analytics.F_DailyLogin(t1, t2, zone, channel, timezone)
	case "UserOnline": //实时登录
		data, err = analytics.F_UserOnline(t1, t2, zone, channel, timezone)
	case "UserLeave": //玩家留存
		data, err = analytics.F_UserLeave(t1, t2, zone, channel, timezone)
	case "OnlineStatistics": //登录统计
		data, err = analytics.F_OnlineStatistics(t1, t2, zone, channel, timezone)
	default:
		SendErr(w, 21, "no support about:"+r.FormValue("type"))
		return
	}

	if err == nil {
		m := make(Message)
		m["message"] = data
		SendSuccess(w, m)
	} else {
		SendErr(w, 22, err.Error())
	}
}
