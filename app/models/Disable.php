<?php

namespace App\Models;


class Disable extends BaseModel
{
    //表名
    public static $tableName = 'disable_log';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }

    public function add($data){
    $this->admin_name = $data['admin_name'];
    $this->admin_no = $data['admin_no'];
    $this->nickname = $data['nickname'];
    $this->server_name = $data['server_name'];
    $this->diserver_id = $data['diserver_id'];
    $this->server_url = $data['server_url'];
    $this->end_time = $data['end_time'];
    $this->created_at = $this->updated_at = time();
    if ($this->create() === false) {
        return false;
    }
        return $this->id;
    }



}