<?php

namespace App\Models;


class ProjectCoin extends BaseModel
{
    //表名
    public static $tableName = 'project_coin';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function getAll($pro_no,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'pro_no = ?1 ',
            'bind' => array(
                1 => $pro_no,
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

    public function getByWhere($pro_no,$whereFiled,$whereData,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => $whereFiled.' = ?1 and pro_no = ?2 ',
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


    public function getByWheres($whereFiled,$whereData,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => $whereFiled[0].' = ?1 and '.$whereFiled[1] .' = ?2',
            'bind' => array(
                1 => $whereData[0],
                2 => $whereData[1],
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


    public function add($data){
        $this->chain_id = $data['chain_id'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->coin_name = $data['coin_name'];
        $this->coin_symbol = $data['coin_symbol'];
        $this->token_contract = $data['token_contract'];
        $this->coin_abi = $data['coin_abi'];
        $this->coin_type = $data['coin_type'];
        $this->pro_no = $data['pro_no'];
        $this->pro_name = $data['pro_name'];
        $this->admin_name = $data['admin_name'];
        $this->admin_no = $data['admin_no'];
        $this->transfer_min = $data['transfer_min'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }

}