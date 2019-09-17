<?php

namespace App\Models;


class SenditemTableLog extends BaseModel
{
    //表名
    public static $tableName = 'table_senditem_log';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function addLog($data){
        $this->admin_no = $data['admin_no'];
        $this->admin_name = $data['admin_name'];
        $this->nickname = $data['nickname'];
        $this->item = $data['item'];
        $this->server_name = $data['server_name'];
        $this->server_url = $data['server_url'];
        $this->diserver_id = $data['diserver_id'];
        $this->is_success = $data['is_success'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }

    public function getList($filed = '*'){
        $result = $this->find([
            'columns' => $filed,
            'order' => 'created_at desc',
        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getByType($type, $filed = '*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'type = ?1',
            'bind' => array(
                1 => $type,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getListByType($type, $filed = '*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'type = ?1',
            'bind' => array(
                1 => $type,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function updateById($id,$data){
        $data['updated_at'] = time();
        $result = $this->findFirst($id);
        if (!$result) {
            return false;
        }
        if($result->save($data) === false){
            return false;
        }
        return true;
    }


}