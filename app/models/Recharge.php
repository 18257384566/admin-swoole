<?php

namespace App\Models;


class Recharge extends BaseModel
{
    //表名
    public static $tableName = 'recharge';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getLast($filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'order' => 'id desc',
        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByOrderId($order_id,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'orderId = ?1',
            'bind' => array(
                1 => $order_id,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByUserId($user_id,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'UserId = ?1',
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