<?php

class PropsendTask extends \App\Core\AppBaseTask
{

    public function handleAction()
    {
        //查询发送道具列表(今日)
        $date = date('Y-m-d',time());
        $filed = 'id,mailtitle,mailcontent,nickname,item,server_url,diserver_id,is_send,server_name';
        $sql = "select $filed from homepage_senditem_crontab where send_time = '$date'";
        $senditems = $this->db->query($sql);
        $senditems->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $senditems = $senditems->fetchAll();

        if(true){
            var_dump('无数据');
            exit;
        }

        //遍历发送道具
        foreach ($senditems as $v){
            if($v['is_send'] == 1){
                continue;
            }
            //发送道具
            $send = [];
            $send['nickname'] = $v['nickname'];
            $send['mailtitle'] = $v['mailtitle'];
            $send['mailcontent'] = $v['mailcontent'];
            $send['itemstr'] = $v['item'];
            $send['zones'] = $v['diserver_id'];
            $sendItem = $this->sendItem($v['server_url'],$send);
            if($sendItem){
                //发送成功
                $update['is_send'] = 1;
                $updateCrontab = $this->getModel('SenditemCrontab')->updateById($v['id'],$update);
                if(!$updateCrontab){
                    $this->getModel('SenditemCrontab')->updateById($v['id'],$update);
                }
            }else{
                //发送失败
                $this->logger('发送失败:'.json_encode($send), "info", 'itemsend');
            }

        }
        var_dump('success');

    }

    //发送道具
    public function sendItem($server_url,$data){
        $url = $server_url.'/manager/senditem';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return true;
    }

}