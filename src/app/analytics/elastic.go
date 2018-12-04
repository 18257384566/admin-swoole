package analytics

import (
	"os"
	"time"

	"gopkg.in/olivere/elastic.v5"
)

var (
	ELK_HOST = "http://127.0.0.1:9200"
)

func init() {
	if env := os.Getenv("ELK_HOST"); env != "" {
		ELK_HOST = env
	}
}

func createClient() (*elastic.Client, error) {

	// Create a client
	client, err := elastic.NewSimpleClient(elastic.SetURL(ELK_HOST))
	//client, err := elastic.NewSimpleClient(elastic.SetURL(ELK_HOST), elastic.SetTraceLog(log.New(os.Stdout, "", log.LstdFlags)))
	if err != nil {
		return nil, err
	}

	return client, nil

}

func NewSearch(t1 int64, t2 int64, zones []string) (*elastic.SearchService, error) {

	client, err := createClient()
	if err != nil {
		return nil, err
	}
	time1 := time.Unix(t1/1000, 0).UTC()
	time2 := time.Unix(t2/1000, 0).UTC()

	days := make([]string, 0, 10)
	startDay := time.Date(time1.Year(), time1.Month(), time1.Day(), 0, 0, 0, 0, time.UTC)
	endDay := time.Date(time2.Year(), time2.Month(), time2.Day(), 0, 0, 0, 0, time.UTC)

	for {
		days = append(days, startDay.Format("2006.01.02"))
		startDay = startDay.Add(24 * time.Hour)
		if startDay.After(endDay) {
			break
		}
	}

	indexs := make([]string, 0, len(days)*len(zones))
	for _, zone := range zones {
		for _, day := range days {
			indexs = append(indexs, "app-zone"+zone+"-"+day)
		}
	}

	search := client.Search()
	search.Index(indexs...)
	search.IgnoreUnavailable(true)
	search.Pretty(true)

	return search, nil
}

func SplitDate(t1 int64, t2 int64, timezone *time.Location) []Days {
	days := make([]Days, 0, 1)
	time1 := time.Unix(t1/1000, 0).In(timezone)

	for {
		isBreak := false
		startTime := time1.Unix() * 1000
		endTime := time.Date(time1.Year(), time1.Month(), time1.Day(), 23, 59, 59, 0, timezone).Unix()*1000 + 999
		if endTime >= t2 {
			endTime = t2
			isBreak = true
		}

		days = append(days, Days{startTime, endTime})

		if isBreak {
			break
		} else {
			time1 = time1.Add(time.Duration((endTime-startTime+1)/1000) * time.Second)
		}
	}

	return days
}

func BuildCommonSerach(t string, t1 int64, t2 int64, zones []string, channel []string) (*elastic.SearchService, *elastic.BoolQuery, error) {
	search, err := NewSearch(t1, t2, zones)
	if err != nil {
		return nil, nil, err
	}

	channels := make([]interface{}, len(channel))
	for k, v := range channel {
		channels[k] = v
	}

	boolQuery := elastic.NewBoolQuery()
	boolQuery.Must(
		elastic.NewRangeQuery("@timestamp").Gte(t1).Lte(t2).Format("epoch_millis"),
		elastic.NewTermQuery("type.keyword", t),
		elastic.NewTermsQuery("channel.keyword", channels...),
	)

	return search, boolQuery, nil
}
