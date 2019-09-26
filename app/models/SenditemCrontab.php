<?php

namespace App\Models;


class SenditemCrontab extends BaseModel
{
    //表名
    public static $tableName = 'senditem_crontab';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function addLog($data){
        $this->admin_no = $data['admin_no'];
        $this->admin_name = $data['admin_name'];
        $this->nickname = $data['mailtitle'];
        $this->nickname = $data['mailcontent'];
        $this->nickname = $data['nickname'];
        $this->item = $data['item'];
        $this->server_name = $data['server_name'];
        $this->server_url = $data['server_url'];
        $this->diserver_id = $data['diserver_id'];
        $this->is_send = $data['is_send'];
        $this->send_time = $data['send_time'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }

    public function getList($filed = '*'){
        $result = $this->find([
            'columns' => $filed,
            'order' => 'created_at desc',
        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }



}