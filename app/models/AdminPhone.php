<?php

namespace App\Models;


class AdminPhone extends BaseModel
{
    //表名
    public static $tableName = 'project_phone_code';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }

    public function add($data){
        $this->phone = $data['phone'];
        $this->code = $data['code'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return true;
    }

    public function getLastByPhone($phone,$filed='*'){
        $result = $this->findFirst(
            [
                'columns' => $filed,
                'conditions' => 'phone = ?1',
                'bind' => array(
                    1 => $phone,
                ),
                'order' => 'id DESC',
            ]
        );
        if($result){
            return $result->toArray();
        }
        return $result;
    }


    public function updateEmail($email,$code,$data,$transaction=false){
        $data['updated_at'] = time();
        $email = $this->findFirst(
            [
                'conditions' => "email = ?1 and code = ?2",
                'bind' => array(
                    1 => $email,
                    2 => $code
                ),
                'order' => 'created_at desc',
            ]
        );
        if(!$email){
            return false;
        }

        if($transaction){
            $email->setTransaction($transaction);
            if($email->save($data) === false){
                $transaction->rollback('email表更新失败');
            }
            return true;
        }
        if(!$email->save($data)){
            return false;
        }
        return true;
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