<?php

namespace App\Models;


class Obonus extends BaseModel
{
    //表名
    public static $tableName = 'obonus';

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

    public function updateByObonusCode($obonus_code,$data)
    {
        $data['update'] = time();
        $result = $this->findFirst(
            [
                'conditions' => 'obonus_code = ?1',
                'bind' => array(
                    1 => $obonus_code,
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




}