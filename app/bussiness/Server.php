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

    public function addDiserver($reqData){
        //判断该server是否存在
        $filed = 'server_name';
        $server = $this->getModel('Server')->getById($reqData['server_id'],$filed);
        if(!$server){
            $this->result['status'] = -1;
            $this->result['msg'] = '该服务器不存在';
            return $this->result;
        }

        //选择参数，加入区服列表
        $reqData['server_name'] = $server['server_name'];
        $saveDiserver = $this->getModel('Diserver')->add($reqData);
        if(!$saveDiserver){
            $this->result['status'] = -1;
            $this->result['msg'] = '添加失败';
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        return $this->result;
    }
}