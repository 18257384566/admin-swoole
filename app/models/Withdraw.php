<?php

namespace App\Models;


class Withdraw extends BaseModel
{
    //表名
    public static $tableName = 'project_withdraw';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getById($id,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'id = ?1',
            'bind' => array(
                1 => $id,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function updateById($id,$data)
    {
        $result = $this->findFirst(
            [
                'conditions' => 'id = ?1',
                'bind' => array(
                    1 => $id,
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