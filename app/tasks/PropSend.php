<?php

class PropSend extends \App\Core\AppBaseTask
{

    public function senditemAction()
    {
        //查询发送道具列表(今日)
        $date = date('Y/m/d',time());
        $filed = 'mailtitle,mailcontent,nickname,item,server_url,diserver_id,is_send';
        $sql = "select $filed from homepage_senditem_crontab where send_time = $date";
        $senditems = $this->db->query($sql);
        $senditems->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $senditems = $senditems->fetchAll();

        if(!$senditems){
            $this->getDI()->get('logger')->log($date.':无发送数据', "info", 'itemsend');
            exit;
        }
var_dump($senditems);
        //遍历发送道具
        foreach ($senditems as $v){

        }
    }

}