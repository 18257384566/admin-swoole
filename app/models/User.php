<?php

namespace App\Models;


class User extends BaseModel
{
    //表名
    public static $tableName = 'user';

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