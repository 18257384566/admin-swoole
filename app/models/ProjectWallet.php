<?php

namespace App\Models;


class ProjectWallet extends BaseModel
{
    //表名
    public static $tableName = 'project_wallets';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getList($pro_no,$type,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'wallet_type = ?1 and pro_no = ?2',
            'bind' => array(
                1 => $type,
                2 => $pro_no,
            ),
        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getById($id,$pro_no,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'id = ?1 and pro_no = ?2',
            'bind' => array(
                1 => $id,
                2 => $pro_no,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByChainAndType($pro_no,$chain_id,$type,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'pro_no = ?1 and chain_id = ?2 and wallet_type = ?3 ',
            'bind' => array(
                1 => $pro_no,
                2 => $chain_id,
                3 => $type,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }


    //获取项目方设置的币种的出账钱包地址
    public function getProWallet($pro_no,$chain_symbol,$wallet_type=2,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'pro_no = ?1 and chain_symbol = ?2 and wallet_type = ?3 ',
            'bind' => array(
                1 => $pro_no,
                2 => $chain_symbol,
                3 => $wallet_type,
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
        $this->address = $data['address'];
        $this->wallet_type = $data['wallet_type'];
        if($this->wallet_type!=1){
            $this->password = $data['password'];
        }else{
            $this->memo = $data['memo'];
        }
        $this->chain_symbol = $data['chain_symbol'];
        $this->chain_id = $data['chain_id'];
        $this->admin_name = $data['admin_name'];
        $this->admin_no = $data['admin_no'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }

    public function updateById($id,$data){
        $result = $this->findFirst($id);
        if (!$result) {
            return false;
        }
        if($result->save($data) === false){
            return false;
        }
        return true;
    }


}