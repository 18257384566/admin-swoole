<?php

namespace App\Models;


class Order extends BaseModel
{
    //表名
    public static $tableName = 'order';

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
        $this->role = $data['role'];
        //$this->is_super = $data['is_super'];
        //$this->permissions = $data['permissions'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }



}