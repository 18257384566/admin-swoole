<?php

namespace App\Models;


class SenditemLog extends BaseModel
{
    //表名
    public static $tableName = 'senditem_log';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function addLog($data){
        $this->admin_no = $data['admin_no'];
        $this->admin_name = $data['admin_name'];
        $this->nickname = $data['nickname'];
        $this->item = $data['item'];
        $this->server_name = $data['server_name'];
        $this->server_url = $data['server_url'];
        $this->diserver_id = $data['diserver_id'];
        $this->is_success = $data['is_success'];
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