package analytics

import (
	"context"
	// "encoding/json"
	"fmt"
	log "github.com/sirupsen/logrus"
	"reflect"
	"time"
	// elastic "gopkg.in/olivere/elastic.v5"
)

//每日登录数
func F_DailyLogin(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (map[string]map[string]int32, error) {

	search, boolQuery, err := BuildCommonSerach("ANALYSIS_LOGIN", t1, t2, zones, channel)
	if err != nil {
		return nil, err
	}

	builder := search.Query(boolQuery).Pretty(true)

	searchResult, err := builder.Do(context.Background())
	log.Errorf("searchResult.Hits.TotalHits :%v, %v", searchResult.Hits.TotalHits, searchResult.Hits.Hits[0].Index)

	if err != nil {
		return nil, err
	}
	if searchResult.Hits.TotalHits > 0 {
		tdStartTime := GetCurrentDateTs(t1, "00:00:00")
		tdEndTime := GetCurrentDateTs(t1, "23:59:59")

		colMap := make(map[string]map[string]int32)
		loginInfo := make(map[string]map[int64]fstLogin)
		var ttyp fstLogin
		for _, item := range searchResult.Each(reflect.TypeOf(ttyp)) {

			if t, ok := item.(fstLogin); ok {
				// fmt.Println("ts", t)
				if _, ok = loginInfo[t.Channel]; !ok {
					loginInfo[t.Channel] = make(map[int64]fstLogin)
					colMap[t.Channel] = map[string]int32{"NewUser": 0, "OldUser": 0}
				}
				if _, ok = loginInfo[t.Channel][t.Uid]; ok {
					continue
				}
				fmt.Println("index", t.Timestamp)
				template := fstLogin{}
				template.Channel = t.Channel
				template.Device = t.Device
				template.Uid = t.Uid
				template.Reg = t.Reg
				loginInfo[t.Channel][t.Uid] = template
				if t.Reg >= tdStartTime && t.Reg <= tdEndTime {
					colMap[t.Channel]["NewUser"] += 1
				} else {
					colMap[t.Channel]["OldUser"] += 1
				}
			}
		}
		return colMap, nil
	}

	return nil, nil
}

// 获取给定时间戳当日某个时间点的时间戳
func GetCurrentDateTs(ts int64, timepoint string) int64 {
	date := time.Unix(ts, 0).Format("2006-01-02") + " " + timepoint
	loc, _ := time.LoadLocation("Local")
	dt, _ := time.ParseInLocation("2006-01-02 15:04:05", date, loc)
	return dt.Unix()
}

//每日登录数
func F_DailyLoginT(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (map[string]map[string]int32, error) {

	// search, boolQuery, err := BuildCommonSerach("ANALYSIS_LOGIN", t1, t2, zones, channel)
	// if err != nil {
	// 	return nil, err
	// }

	// builder := search.Query(boolQuery).Pretty(true)

	// searchResult, err := builder.Do(context.Background())
	// log.Errorf("searchResult.Hits.TotalHits :%v", searchResult.Hits.TotalHits)

	// if err != nil {
	// 	return nil, err
	// }
	// if searchResult.Hits.TotalHits > 0 {
	// 	tdStartTime := GetCurrentDateTs(t1, "00:00:00")
	// 	tdEndTime := GetCurrentDateTs(t1, "23:59:59")

	// 	colMap := &ChanUserLoginInfo{}
	// 	loginInfo := make(map[string]map[int64]fstLogin)
	// 	var ttyp fstLogin
	// 	for _, item := range searchResult.Each(reflect.TypeOf(ttyp)) {

	// 		if t, ok := item.(fstLogin); ok {
	// 			if _, ok = loginInfo[t.Channel]; !ok {
	// 				loginInfo[t.Channel] = make(map[int64]fstLogin)
	// 				colMap[t.Channel] = map[string]int32{"NewUser": 0, "OldUser": 0}
	// 			}
	// 			if _, ok = loginInfo[t.Channel][t.Uid]; ok {
	// 				continue
	// 			}
	// 			template := fstLogin{}
	// 			template.Channel = t.Channel
	// 			template.Device = t.Device
	// 			template.Uid = t.Uid
	// 			template.Reg = t.Reg
	// 			loginInfo[t.Channel][t.Uid] = template
	// 			if t.Reg >= tdStartTime && t.Reg <= tdEndTime {
	// 				colMap[t.Channel]["NewUser"] += 1
	// 			} else {
	// 				colMap[t.Channel]["OldUser"] += 1
	// 			}
	// 		}
	// 	}
	// 	return colMap, nil
	// }

	return nil, nil
}
