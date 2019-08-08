<?php

namespace App\Models;


class SystemChain extends BaseModel
{
    //表名
    public static $tableName = 'system_chain';

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

    public function getByWhere($whereFiled,$whereData,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => $whereFiled.' = ?1',
            'bind' => array(
                1 => $whereData,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }


    public function add($data){
        $this->chain_name = $data['chain_name'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->chain_intro = $data['chain_intro'];
        $this->publish_date = $data['publish_date'];
        $this->issuance_total = $data['issuance_total'];
        $this->circulate_total = $data['circulate_total'];
        $this->initial_price = $data['initial_price'];
        $this->white_paper = $data['white_paper'];
        $this->website = $data['website'];
        $this->blockchain = $data['blockchain'];
        $this->ip = $data['ip'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }
}