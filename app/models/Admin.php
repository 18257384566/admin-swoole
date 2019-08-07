<?php

namespace App\Models;


class Admin extends BaseModel
{
    //表名
    public static $tableName = 'admin';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getByAdmin($name,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'admin_name = ?1',
            'bind' => array(
                1 => $name,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
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

    public function getSuper($pro_no,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'pro_no = ?1 and is_super = ?2 and status = ?3',
            'bind' => array(
                1 => $pro_no,
                2 => 1,
                3 => 1,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function updateById($id,$data)
    {
        $data['update'] = time();
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


    public function getPhone($phone,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'phone = ?1',
            'bind' => array(
                1 => $phone,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }


    public function add($data){
        $this->admin_no = $data['admin_no'];
        $this->admin_name = $data['admin_name'];
        $this->real_name = $data['real_name'];
        $this->password = $data['password'];
        $this->phone = $data['phone'];
        $this->is_super = $data['is_super'];
        $this->permissions = $data['permissions'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }



}