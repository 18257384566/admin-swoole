<?php

namespace App\Models;


class ProjectWalletsAssets extends BaseModel
{
    //表名
    public static $tableName = 'project_wallets_assets';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }

    public function add($data){
        $this->batch_no = $data['batch_no'];
        $this->address = $data['address'];
        $this->chain_symbol = $data['chain_symbol'];
        $this->chain_id = $data['chain_id'];
        $this->chain_fee_balance = $data['chain_fee_balance'];
        $this->coin_symbol = $data['coin_symbol'];
        $this->coin_id = $data['coin_id'];
        $this->coin_balance = $data['coin_balance'];
        $this->pro_no = $data['pro_no'];
        $this->pro_name = $data['pro_name'];
        $this->created_at = $this->updated_at = $data['created_at'];
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }
}