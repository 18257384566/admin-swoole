<?php

namespace App\Models;


class ProjectPermission extends BaseModel
{
    //表名
    public static $tableName = 'system_project_permissions';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getAll($filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => '',
            'bind' => array(

            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByTopId($top_id,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'top_id = ?1',
            'bind' => array(
                '1'=>$top_id,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByNameTopId($name,$top_id,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'name = ?1 and top_id=?2',
            'bind' => array(
                '1'=>$name,
                '2'=>$top_id,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function add($data){
        $this->top_id = $data['top_id'];
        $this->name = $data['name'];
        $this->show_name = $data['show_name'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }


}