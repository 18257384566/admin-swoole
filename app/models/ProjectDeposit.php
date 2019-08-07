<?php
/**
 * Created by PhpStorm.
 * User: Echo
 * Date: 2018/11/9
 * Time: 20:44
 */

namespace App\Models;


class ProjectDeposit extends BaseModel
{
    //表名
    public static $tableName = 'project_deposit';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function add($data){
        if($this->create($data) == false){
            return false;
        }
        return true;
    }


}