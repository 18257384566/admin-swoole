<?php

namespace App\Models;


class Project extends BaseModel
{
    //表名
    public static $tableName = 'system_project_info';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getDetail($pro_no,$filed='*'){
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

    public function add($data){
        $this->pro_no = $data['pro_no'];
        $this->pro_name = $data['pro_name'];
        $this->contacts_name = $data['contacts_name'];
        $this->contacts_phone = $data['contacts_phone'];
        $this->corporate_name = $data['corporate_name'];
        $this->security_url = $data['security_url'];
        $this->encryption_security_url = $data['encryption_security_url'];
        $this->project_hash_url = $data['project_hash_url'];
        $this->project_wallet_url = $data['project_wallet_url'];
        $this->login_url = $data['login_url'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }


}