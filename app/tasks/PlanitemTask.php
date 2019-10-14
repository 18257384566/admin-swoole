<?php

class PlanitemTask extends \App\Core\AppBaseTask
{

    public function planAction()
    {
        //检测重连机制
        $this->getBusiness('Mysqlreconnect')->reconnect('planitem');

        $sql = "select * from homepage_senditem_crontab";
        $senditems = $this->db->query($sql);
        $senditems->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $senditems = $senditems->fetchAll();

        var_dump($senditems);

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