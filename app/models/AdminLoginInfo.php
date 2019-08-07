<?php

namespace App\Models;


class AdminLoginInfo extends BaseModel
{
    //表名
    public static $tableName = 'project_admin_login_info';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function add($data){
        $this->pro_no = $data['pro_no'];
        $this->admin_no = $data['admin_no'];
        $this->admin_name = $data['admin_name'];
        $this->ip = $data['ip'];
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