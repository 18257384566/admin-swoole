package analytics

import (
	"context"
	"encoding/json"
	"time"

	elastic "gopkg.in/olivere/elastic.v5"
)

//每日游戏时长
func F_DailyPlayTime(t1 int64, t2 int64, zones []string, channel []string, timezone *time.Location) (*PlayTimeBuckets, error) {

	search, boolQuery, err := BuildCommonSerach("ANALYSIS_DAILYPLAYTIME", t1, t2, zones, channel)
	if err != nil {
		return nil, err
	}

	builder := search.Query(boolQuery).Size(0).Pretty(true)
	filterUserid := elastic.NewTermsAggregation().Field("uid").Size(5000)
	sumAggregation := elastic.NewSumAggregation().Field("playtime")
	filterUserid.SubAggregation("Count", sumAggregation)
	builder = builder.Aggregation("playtime", filterUserid)

	searchResult, err := builder.Do(context.Background())

	if err != nil {
		return nil, err
	}

	if searchResult.Hits.TotalHits > 0 {
		r := &PlayTimeBuckets{}
		t := &DailyPlayTimeBuckets{}
		if err := json.Unmarshal(*searchResult.Aggregations["playtime"], t); err != nil {
			return nil, err
		}

		d := make(map[int]uint32)
		for _, v := range t.Buckets {
			k := int(v.Value.Value/60) * 60
			d[k] += 1
		}

		for k, v := range d {
			r.Buckets = append(r.Buckets, FKeyCount{float64(k), v})
		}
		r.Count = len(t.Buckets)

		return r, nil
	}

	return nil, nil
}
