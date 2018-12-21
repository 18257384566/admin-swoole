package analytics

import (
	"context"
	"fmt"
	log "github.com/sirupsen/logrus"
	elastic "gopkg.in/olivere/elastic.v5"
	"reflect"
	"sort"
	"strings"
	"time"
)

const SEARCHRECORDS int = 10000

//登录统计
func F_DailyLogin(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (map[string]map[string]int32, error) {
	search, boolQuery, err := BuildCommonSerach("ANALYSIS_LOGIN", t1, t2, zones, channel)
	if err != nil {
		return nil, err
	}

	builder := search.Query(boolQuery).Size(SEARCHRECORDS).Pretty(true)

	searchResult, err := builder.Do(context.Background())

	if err != nil {
		return nil, err
	}

	if searchResult.Hits.TotalHits > 0 {
		colMap := make(map[string]map[string]int32)
		resMap := make(map[string]map[string]int32)
		loginInfo := make(map[string]map[int64]LoginInfo)
		dateS := make([]string, 0, len(searchResult.Hits.Hits))
		var ttyp LoginInfo
		for _, item := range searchResult.Each(reflect.TypeOf(ttyp)) {

			if t, ok := item.(LoginInfo); ok {
				date := GetUTCToFormat(t.Timestamp, "2006-01-02")
				tdStartTime := GetDateToTs(date+" 00:00:00", "2006-01-02 15:04:05")
				tdEndTime := GetDateToTs(date+" 23:59:59", "2006-01-02 15:04:05")
				if _, ok = loginInfo[date]; !ok {
					loginInfo[date] = make(map[int64]LoginInfo)
					colMap[date] = map[string]int32{"NewUser": 0, "OldUser": 0}
					dateS = append(dateS, date)
				}
				if _, ok = loginInfo[date][t.Uid]; ok {
					continue
				}
				template := LoginInfo{}
				template.Channel = t.Channel
				template.Device = t.Device
				template.Uid = t.Uid
				template.Reg = t.Reg
				loginInfo[date][t.Uid] = template
				if t.Reg >= tdStartTime && t.Reg <= tdEndTime {
					colMap[date]["NewUser"] += 1
				} else if t.Reg <= tdStartTime {
					colMap[date]["OldUser"] += 1
				}
			}
		}
		sort.Sort(sort.Reverse(sort.StringSlice(dateS)))
		for _, v := range dateS {
			resMap[v] = colMap[v]
		}
		return resMap, nil
	}

	return nil, nil
}

//玩家留存
func F_UserLeave(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (map[string]*LeaveRateInfo, error) {
	search, boolQuery, err := BuildCommonSerach("ANALYSIS_LOGIN", t1, t2, zones, channel)

	builder := search.Query(boolQuery).Size(SEARCHRECORDS).Pretty(true)

	searchResult, err := builder.Do(context.Background())
	log.Errorf("searchResult.Hits.TotalHits :%v", searchResult.Hits.TotalHits)

	if err != nil {
		return nil, err
	}

	if searchResult.Hits.TotalHits > 0 {
		dateFormat := "2006-01-02"
		endDate := time.Unix(t2, 0).AddDate(0, 0, -30).Format(dateFormat)
		actMap := make(map[string]*ActInfo)
		dateS := make([]string, 0, 30) // 排序用
		var ttyp LoginInfo
		for _, item := range searchResult.Each(reflect.TypeOf(ttyp)) {

			if t, ok := item.(LoginInfo); ok {
				date := GetUTCToFormat(t.Timestamp, "2006-01-02")
				tdStartTime := GetDateToTs(date+" 00:00:00", "2006-01-02 15:04:05")
				tdEndTime := GetDateToTs(date+" 23:59:59", "2006-01-02 15:04:05")

				if _, ok = actMap[date]; !ok {
					dateS = append(dateS, date)
					actMap[date] = &ActInfo{
						NewNum:     0,
						OnlineNum:  0,
						NewList:    make(map[int64]int64),
						OnlineList: make(map[int64]int64),
					}
				}
				if _, ok = actMap[date].OnlineList[t.Uid]; !ok {
					actMap[date].OnlineList[t.Uid] = t.Uid
					actMap[date].OnlineNum++
				}
				if t.Reg >= tdStartTime && t.Reg <= tdEndTime {
					if _, ok := actMap[date].NewList[t.Uid]; !ok {
						actMap[date].NewList[t.Uid] = t.Uid
						actMap[date].NewNum++
					}
				}
			}
		}
		sort.Strings(dateS)
		leaveMap := make(map[string]map[int]int)
		leaveRateMap := make(map[string]*LeaveRateInfo)

		// 计算留存量
		for i := 2; i <= 30; i++ {
			if i >= 8 && i != 14 && i != 30 {
				continue
			}
			for _, dt := range dateS {
				df, _ := time.ParseInLocation(dateFormat, dt, timezone)
				fstkey := df.AddDate(0, 0, -(i - 1)).Format(dateFormat)

				if _, ok := leaveMap[dt]; !ok {
					leaveMap[dt] = make(map[int]int)
				}
				if _, ok := leaveMap[dt][i]; !ok {
					leaveMap[dt][i] = 0
				}

				if _, ok := leaveMap[fstkey][i]; ok {
					fmt.Println("前一天", fstkey)
					for _, uid := range actMap[dt].OnlineList {
						fmt.Println("用户id", uid)
						if _, ok := actMap[fstkey].NewList[uid]; ok {
							leaveMap[fstkey][i]++
						}
					}
				}
			}
		}

		// 计算留存率
		for i := 2; i <= 30; i++ {
			if i >= 8 && i != 14 && i != 30 {
				continue
			}
			for dt, leaveInfo := range leaveMap {
				if dt > endDate {
					break
				}
				if _, ok := leaveRateMap[dt]; !ok {
					leaveRateMap[dt] = &LeaveRateInfo{
						NewNum:    0,
						LeaveRate: make(map[int]string),
					}
				}
				newNum := actMap[dt].NewNum
				leaveRateMap[dt].NewNum = newNum
				for key, value := range leaveInfo {
					var res string
					if 0 == newNum {
						res = "0.00"
					} else {
						res = fmt.Sprintf("%.2f", float64(value)/float64(newNum)*float64(100))
					}
					leaveRateMap[dt].LeaveRate[key] = res + "%"
				}
			}
		}
		return leaveRateMap, nil
	}
	return nil, nil
}

//实时在线
func F_UserOnline(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (*UserOnlineAck, error) {
	search, err := NewSearch(t1, t2, zones)
	if err != nil {
		return nil, err
	}

	boolQuery := elastic.NewBoolQuery()
	boolQuery.Must(
		elastic.NewRangeQuery("@timestamp").Gte(t1*1000).Lte(t2*1000).Format("epoch_millis"),
		elastic.NewTermQuery("type.keyword", "ANALYSIS_ACU"),
	)

	builder := search.Query(boolQuery).Size(SEARCHRECORDS).Pretty(true)

	searchResult, err := builder.Do(context.Background())

	if err != nil {
		return nil, err
	}

	if searchResult.Hits.TotalHits > 0 {
		res := &UserOnlineAck{}
		countMap := make(map[string]int32)
		var ttyp UserOnline
		for _, item := range searchResult.Each(reflect.TypeOf(ttyp)) {

			if t, ok := item.(UserOnline); ok {
				timePoint := GetUTCToFormat(t.Time, "2006-01-02 15:04:05")[11:16]
				if _, ok := countMap[timePoint]; !ok {
					res.TimeS = append(res.TimeS, timePoint)
				}

				countMap[timePoint] += t.Count

			}
		}
		sort.Strings(res.TimeS)
		for _, v := range res.TimeS {
			res.CountS = append(res.CountS, countMap[v])
		}
		return res, nil
	}
	return nil, nil
}

// 在线统计
func F_OnlineStatistics(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (map[string]*OnlineStatisticsInfo, error) {

	search, err := NewSearch(t1, t2, zones)
	if err != nil {
		return nil, err
	}

	boolQuery := elastic.NewBoolQuery()
	boolQuery.Must(
		elastic.NewRangeQuery("@timestamp").Gte(t1*1000).Lte(t2*1000).Format("epoch_millis"),
		elastic.NewTermQuery("type.keyword", "ANALYSIS_ACU"),
	)

	builder := search.Query(boolQuery).Size(SEARCHRECORDS).Pretty(true)

	searchResult, err := builder.Do(context.Background())

	if err != nil {
		return nil, err
	}

	if searchResult.Hits.TotalHits > 0 {
		onlineInfo := make(map[string]*OnlineStatisticsInfo)
		var ttyp UserOnline
		for _, item := range searchResult.Each(reflect.TypeOf(ttyp)) {

			if t, ok := item.(UserOnline); ok {
				date := GetUTCToFormat(t.Time, "2006-01-02")
				if _, ok := onlineInfo[date]; !ok {
					onlineInfo[date] = &OnlineStatisticsInfo{
						Max:   0,
						Count: 0,
						Sum:   0,
					}
				}
				onlineInfo[date].Sum += t.Count
				if t.Count > onlineInfo[date].Max {
					onlineInfo[date].Max = t.Count
				}
				onlineInfo[date].Count++
			}
		}
		return onlineInfo, nil
	}

	return nil, nil
}

/*
* 公用方法
 */

// 日期转换时间戳
func GetDateToTs(date, format string) int64 {
	loc, _ := time.LoadLocation("Local")
	dt, _ := time.ParseInLocation(format, date, loc)
	return dt.Unix()
}

func GetUTCToFormat(date, format string) string {
	dt := strings.Replace(date, "T", " ", -1)
	dt = strings.Replace(dt, ".000Z", "", -1)

	dtFormat, _ := time.ParseInLocation("2006-01-02 15:04:05", dt, time.UTC)
	res := time.Unix(dtFormat.Unix(), 0).Format(format)

	return res
}
