<?php

namespace App\Models;


class ProjectWalletBatch extends BaseModel
{
    //表名
    public static $tableName = 'project_wallets_batch';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function add($data){
        $this->batch_no = $data['batch_no'];
        $this->chain_id = $data['chain_id'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->address_total = $data['address_total'];
        $this->password_prompt = $data['password_prompt'];
        $this->password = $data['password'];
        $this->pro_no = $data['pro_no'];
        $this->pro_name = $data['pro_name'];
        $this->admin_name = $data['admin_name'];
        $this->admin_no = $data['admin_no'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
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


    public function getByWhere($pro_no,$whereFiled,$whereData,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => $whereFiled.' = ?1 and pro_no = ?2',
            'bind' => array(
                1 => $whereData,
                2 => $pro_no,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }
}