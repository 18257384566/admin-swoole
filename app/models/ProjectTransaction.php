<?php

namespace App\Models;


class ProjectTransaction extends BaseModel
{
    //表名
    public static $tableName = 'project_transaction';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function add($data){
        $this->transaction_no = $data['transaction_no'];
        $this->batch_no = $data['batch_no'];
        $this->title = $data['title'];
        $this->chain_id = $data['chain_id'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->coin_symbol = $data['coin_symbol'];
        $this->coin_id = $data['coin_id'];
        $this->count = $data['count'];
        $this->success = $data['success'];
        $this->fail = $data['fail'];
        $this->incomplete = $data['incomplete'];
        $this->amount = $data['amount'];
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

    public function getByWheres($pro_no,$whereFiled,$whereData,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => ' pro_no = ?1 and '.$whereFiled[0].' = ?2 and '.$whereFiled[1] .' = ?3',
            'bind' => array(
                1 => $pro_no,
                2 => $whereData[0],
                3 => $whereData[1],
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
            'conditions' => ' pro_no = ?1 and '.$whereFiled.' = ?2 ',
            'bind' => array(
                1 => $pro_no,
                2 => $whereData,
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

}