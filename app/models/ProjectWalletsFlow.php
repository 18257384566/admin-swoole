<?php

namespace App\Models;


class ProjectWalletsFlow extends BaseModel
{
    //表名
    public static $tableName = 'project_wallets_flow';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }

    public function add($data){
        $this->from_address = $data['from_address'];
        $this->to_address = $data['to_address'];
        $this->chain_id = $data['chain_id'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->coin_symbol = $data['coin_symbol'];
        $this->coin_id = $data['coin_id'];
        $this->hash = $data['hash'];
        $this->coin_chain_amount = $data['coin_chain_amount'];
        $this->coin_amount = $data['coin_amount'];
        $this->obj_name = $data['obj_name'];
        $this->obj_id = $data['obj_id'];
        $this->flow_type = $data['flow_type'];
        $this->pro_no = $data['pro_no'];
        $this->pro_name = $data['pro_name'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
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