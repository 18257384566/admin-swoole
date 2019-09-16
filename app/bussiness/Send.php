<?php

namespace App\Bussiness;

use App\Libs\SendSms;

class Send extends BaseBussiness
{
    public function propServer($admin,$reqData){
        //整理要发送的内容
        $send = [];
        $send['nickname'] = $reqData['nickname'];
        $send['mailtitle'] = $reqData['mailtitle'];
        $send['mailcontent'] = $reqData['mailcontent'];
        $send['itemstr'] = '';

        foreach ($reqData['itemSelected'] as $v){
            $send['itemstr'] .= $v.';';
        }
        $send['itemstr'] = rtrim($send['itemstr'],';');

        //分服务器发送道具
        foreach ($reqData['server_id'] as $server_id){
            //判断服务器是否存在
            $filed = 'server_name,diserver_id,url,server_name';
            $server = $this->getModel('Server')->getById($server_id,$filed);
            if(!$server){
                continue;
            }

            //发送道具请求
            $send['zones'] = $server['diserver_id'];
            $sendItem = $this->getBussiness('GameApi')->sendItem($server['url'],$send);

            //存储记录
            $log = [];
            $log['admin_name'] = $admin['account'];
            $log['admin_no'] = $admin['admin_no'];
            $log['nickname'] = $reqData['nickname'];
            $log['item'] = $send['itemstr'];
            $log['server_name'] = $server['server_name'];
            $log['diserver_id'] = $server['diserver_id'];
            $log['server_url'] = $server['url'];
            if(!$sendItem){
                $log['is_success'] = 0;
            }else{
                $log['is_success'] = 1;
            }
            $this->getModel('SenditemLog')->addLog($log);

        }
    }


}