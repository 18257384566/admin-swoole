package analytics

type FCount struct {
	Value float64 `json:"value"`
}

type Days struct {
	t1 int64
	t2 int64
}

type Agg_FCount struct {
	Value FCount `json:"Count"`
}

type FKeyCount struct {
	Key   float64 `json:"key"`
	Count uint32  `json:"doc_count"`
}

type PlayTimeBuckets struct {
	Count   int
	Buckets []FKeyCount `json:"buckets"`
}

type DailyPlayTimeBuckets struct {
	Buckets []Agg_FCount `json:"buckets"`
}

// 登陆信息
type LoginInfo struct {
	Channel   string `json:"channel"`
	Device    string `json:"device"`
	Uid       int64  `json:"uid"`
	Reg       int64  `json:"reg"`
	Timestamp string `json:"@timestamp"`
}

type UsersLoginInfo struct {
	RegNum   int64 `json:"regnum"`
	LoginNum int64 `json:"loginnum"`
	DAU      int64 `json:"dau"` //登录用户数-新增用户数
}

type UserOnline struct {
	Time         string         `json:"@timestamp"`
	ChannelState map[string]int `json:"channelState"`
	Count        int32          `json:"count"`
}

type ActInfo struct {
	NewNum     int
	OnlineNum  int
	NewList    map[int64]int64
	OnlineList map[int64]int64
}

type UserOnlineAck struct {
	TimeS  []string
	CountS []int32
}

type OnlineStatisticsInfo struct {
	Max   int32
	Count int32
	Sum   int32
}

type LeaveRateInfo struct {
	NewNum    int
	LeaveRate map[int]string
}
