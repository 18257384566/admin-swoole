package paynotify

import (
	"app/etcd"
	"app/proto"
	"context"
	"encoding/json"
	"errors"
	"log"
)

type Platform uint8

const (
	_ Platform = iota
	PLATFORM_FEILIU
)

type Channel uint8

const (
	_ Channel = iota
	CHANNEL_FEILIU
)

type System uint8

const (
	_ System = iota
	SYSTEM_IOS
	SYSTEM_ANDROID
)

type Notify struct {
	Platform               //平台 如棱镜、UC、飞流
	Channel                //渠道 如飞流、小米
	System                 //系统 IOS、Android
	PlatformOrderId string //平台订单号
	GameOrderId     uint64 //游戏内订单号
	Amount          uint64 //支付金额（分）
}

func NotifyToGameServer(zone string, notify *Notify) error {

	success := false
	zone = etcd.GetRealZone(zone)

	if conns := etcd.GetGameAllConn(zone); conns != nil {

		message, _ := json.Marshal(notify)

		data := &proto.Center_Req{4, message} //支付通知

		for k, conn := range conns {
			if conn != nil {
				cli := proto.NewGameServiceClient(conn)
				if _, err := cli.Manage(context.Background(), data); err != nil {
					log.Println("错误：订单通知server-zone%s error，index:%d，err:%s\n", zone, k, err.Error())
				} else {
					success = true
					break
				}
			}
		}
	} else {
		log.Println("错误：订单通知无法连接GameServer，返回错误，尝试让Notify再次通知", notify.GameOrderId, notify.PlatformOrderId)
		return errors.New("cannot connect to gameserver")
	}

	if !success {
		log.Println("错误：订单通知GAME全部失败，返回错误，尝试让Notify再次通知")
		return errors.New("notify gameserver error")
	}
	return nil
}
