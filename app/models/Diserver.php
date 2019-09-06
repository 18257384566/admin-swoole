<?php

namespace App\Models;


class Diserver extends BaseModel
{
    //表名
    public static $tableName = 'diserver';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }


    public function add($data){
        $this->server_id = $data['server_id'];
        $this->server_name = $data['server_name'];
        $this->diserver_id = $data['diserver_id'];
        $this->diserver_name = $data['diserver_name'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }

    public function getByServerName($server_name, $filed = '*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'server_name = ?1',
            'bind' => array(
                1 => $server_name,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function delDiserver($id){
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
        if ($result->delete() === false) {
            return false;
        }
        return true;
    }

    public function getList($filed = '*'){
        $result = $this->find([
            'columns' => $filed,
        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function getById($id, $filed = '*'){
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

    public function getByServerId($server_id, $filed = '*'){
        $result = $this->find([
            'columns' => $filed,
            'conditions' => 'server_id = ?1',
            'bind' => array(
                1 => $server_id,
            ),

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;
    }
}