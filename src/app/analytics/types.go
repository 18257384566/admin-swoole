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
	Buckets []FKeyCount `json:"buckets`
}

type DailyPlayTimeBuckets struct {
	Buckets []Agg_FCount `json:"buckets`
}
