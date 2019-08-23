<?php

namespace App\Models;


class Construction extends BaseModel
{
    //表名
    public static $tableName = 'construction';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getList($filed = '*'){
        $result = $this->find([
            'columns' => $filed,

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }



}