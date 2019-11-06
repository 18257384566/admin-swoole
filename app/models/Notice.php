<?php

namespace App\Models;


class Notice extends BaseModel
{
    //表名
    public static $tableName = 'notice';

    public function initialize()
    {
        parent::initialize();
        $this->setTableName(self::$tableName);
    }

    public function add($data){
        $this->admin_no = $data['admin_no'];
        $this->admin_name = $data['admin_name'];
        $this->channel = $data['channel'];
        $this->notice = $data['notice'];
        $this->remark = $data['remark'];
        $this->start_time = $data['start_time'];
        $this->created_at = $this->updated_at = time();
        if ($this->create() === false) {
            return false;
        }
        return $this->id;
    }

    public function getByChannel($channel,$filed='*'){
        $result = $this->findFirst([
            'columns' => $filed,
            'conditions' => 'channel = ?1',
            'bind' => array(
                1 => $channel,
            ),
            'order' => 'created_at DESC',

        ]);
        if($result){
            return $result->toArray();
        }
        return $result;

    }

    public function updateById($id,$data)
    {
        $data['update'] = time();
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