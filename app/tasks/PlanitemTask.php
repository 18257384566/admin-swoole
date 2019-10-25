<?php

class PlanitemTask extends \App\Core\AppBaseTask
{

    public function planAction()
    {
//        while (true){
            //检测重连机制
//            $this->getBusiness('Mysqlreconnect')->reconnect('planitem');

            $time = time();
            $filed = 'id,mailtitle,mailcontent,nickname,item,server_url,diserver_id,is_send,server_name';
            $sql = "select $filed from homepage_senditem_plan where send_time < $time and is_send = 0";
            $senditems = $this->db->query($sql);
            $senditems->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $senditems = $senditems->fetchAll();

            if($senditems){
                //遍历发送道具
                foreach ($senditems as $v){
                    if($v['is_send'] != 0){
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
                        $updateCrontab = $this->getModel('SenditemPlan')->updateById($v['id'],$update);
                        if(!$updateCrontab){
                            $this->getModel('SenditemPlan')->updateById($v['id'],$update);
                        }
                    }else{
                        //发送失败
                        $update['is_send'] = -1;
                        $this->getModel('SenditemPlan')->updateById($v['id'],$update);
                        //$this->getDI()->get('logger')->log('发送失败:'.json_encode($send), "info", '/cache/itemplan');
                    }

                }
            }


            //var_dump('success');
//            sleep(60);
//        }
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