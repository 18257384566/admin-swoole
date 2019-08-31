<?php

namespace App\Bussiness;


class Server extends BaseBussiness
{
    public function addServer($reqData){
        //判断该服务器名是否被使用
        $filed = 'id';
        $isset = $this->getModel('Server')->getByServerName($reqData['server_name'],$filed);
        if($isset){
            $this->result['status'] = -1;
            $this->result['msg'] = '该服务器名已被使用';
            return $this->result;
        }

        //添加记录
        $add = $this->getModel('Server')->add($reqData);
        if(!$add){
            $this->result['status'] = -1;
            $this->result['msg'] = '添加失败';
            return $this->result;
        }

        $this->result['status'] = -1;
        $this->result['msg'] = '添加成功';
        return $this->result;
    }
}