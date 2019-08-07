<?php

namespace App\Models;


class ProjectWalletAsset extends BaseModel
{
    //表名
    public static $tableName = 'project_wallets_assets';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
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

    public function getByAddressAndCoin($address,$coin_type,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'address = ?1 and coin_symbol = ?2',
            'bind' => array(
                1 => $address,
                2 => $coin_type,
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