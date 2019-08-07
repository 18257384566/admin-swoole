<?php

namespace App\Models;


class ProjectTransactionInfo extends BaseModel
{
    //表名
    public static $tableName = 'project_transaction_info';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function add($data){
        $this->transaction_no = $data['transaction_no'];
        $this->batch_no = $data['batch_no'];
        $this->chain_id = $data['chain_id'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->coin_symbol = $data['coin_symbol'];
        $this->coin_id = $data['coin_id'];
        $this->wallet_address = $data['wallet_address'];
        $this->hash = $data['hash'];
        $this->fee = $data['fee'];
        $this->amount = $data['amount'];
        $this->status = $data['status'];
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

//    public function getByWhereFirst($whereFiled,$whereData,$filed='*'){
//        $result = $this->findFirst([
//            'columns' => $filed,
//            'conditions' => $whereFiled.' = ?1',
//            'bind' => array(
//                1 => $whereData,
//            ),
//
//        ]);
//        if($result){
//            return $result->toArray();
//        }
//        return $result;
//    }


    public function getByWheres($whereFiled,$whereData,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => $whereFiled[0].' = ?1 and '.$whereFiled[1] .' = ?2 and '.$whereFiled[2] .' = ?3',
            'bind' => array(
                1 => $whereData[0],
                2 => $whereData[1],
                3 => $whereData[2],
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByWheresForEos($whereFiled,$whereData,$filed='*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => $whereFiled[0].' = ?1 and '.$whereFiled[1] .' = ?2 and '.$whereFiled[2] .' = ?3',
            'bind' => array(
                1 => $whereData[0],
                2 => $whereData[1],
                3 => $whereData[2],
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

    public function updateByWhere($data,$whereFiled,$whereData)
    {
        $result = $this->findFirst(
            [
                'conditions' => $whereFiled.' = ?1',
                'bind' => array(
                    1 => $whereData,
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