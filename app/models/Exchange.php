<?php

namespace App\Models;


class Exchange extends BaseModel
{
    //表名
    public static $tableName = 'exchange';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getByExchangeCode($exchange_code,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'exchange_code = ?1',
            'bind' => array(
                1 => $exchange_code,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function updateByExchangeCode($exchange_code,$data)
    {
        $data['update'] = time();
        $result = $this->findFirst(
            [
                'conditions' => 'exchange_code = ?1',
                'bind' => array(
                    1 => $exchange_code,
                ),
            ]
        );
        if (!$result) {
            return false;
        }
        if ($result->save($data) === false) {
            return false;
        }
        return true;
    }

    public function getByUserCardNo($user_name,$card_no,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'user_name = ?1 and card_no = ?2',
            'bind' => array(
                1 => $user_name,
                2 => $card_no,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }


}