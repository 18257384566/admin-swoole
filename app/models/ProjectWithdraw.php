<?php
/**
 * Created by PhpStorm.
 * User: Echo
 * Date: 2018/11/9
 * Time: 20:44
 */

namespace App\Models;


class ProjectWithdraw extends BaseModel
{
    //表名
    public static $tableName = 'project_withdraw';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function createWithdraw($order){
        if($this->create($order) == false){
            return false;
        }
        return true;
    }


    //根据订单编号和项目编号,查询数据
    public function getWithdraw($withdraw_no,$pro_no,$filed="*"){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'withdraw_no = ?1 and pro_no = ?2',
            'bind' => array(
                1 => $withdraw_no,
                2 => $pro_no,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }
}