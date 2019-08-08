<?php

namespace App\Models;


class SystemProjectInfo extends BaseModel
{
    //表名
    public static $tableName = 'system_project_info';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getAll($filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'status = ?1',
            'bind' => array(
                1 => 1,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getById($id,$filed = '*'){
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

    public function updateById($id,$data){
        $data['updated_at'] = time();
        $result = $this->findFirst($id);
        if (!$result) {
            return false;
        }
        if($result->save($data) === false){
            return false;
        }
        return true;
    }


    public function getByProNo($pro_no,$filed = '*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'pro_no = ?1',
            'bind' => array(
                1 => $pro_no,
            ),
        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }


}