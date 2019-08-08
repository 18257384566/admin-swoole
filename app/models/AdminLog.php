<?php

namespace App\Models;


class AdminLog extends BaseModel
{
    //表名
    public static $tableName = 'admin_login_log';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function addLog($data){
        $this->admin_no = $data['admin_no'];
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->admin_name = $data['admin_name'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }



}