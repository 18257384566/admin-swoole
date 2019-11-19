<?php

namespace App\Models;


class LoginLog extends BaseModel
{
    //表名
    public static $tableName = 'login_log';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }

    public function getByUserId($user_id,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'user_id = ?1',
            'bind' => array(
                1 => $user_id,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }




}