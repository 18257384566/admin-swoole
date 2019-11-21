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

    public function getByUserIdDate($user_id,$date,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => "user_id = ?1 and date = ?2",
            'bind' => array(
                1 => $user_id,
                2 => $date,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByUserIdTime($user_id,$time,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => "user_id = ?1 and time = ?2",
            'bind' => array(
                1 => $user_id,
                2 => $time,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }




}