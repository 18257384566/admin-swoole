<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class SystemChain extends BaseBussiness
{
    public function getAll(){
        return $this->getModel('SystemChain')->getAll($field='*');
    }

    public function getByWhere($whereFiled,$whereData){
        return $this->getModel('SystemChain')->getByWhere($whereFiled,$whereData,$filed='*');
    }


    public function add($data){
        $result = $this->getByWhere('chain_symbol',$data['chain_symbol']);
        if($result){
            $this->result['status'] = -1;
            $this->result['msg'] = '此公链已存在';
            return $this->result;
        }

        $add = $this->getModel('SystemChain')->add($data);
        if(!$add){
            $this->result['status'] = -1;
            $this->result['msg'] = '添加失败';
            return $this->result;
        }

        //redis
        if (!$this->redis->sismember('wallet_center:set:currenylist', $data['chain_symbol'])) {
            $this->redis->sAdd('wallet_center:set:currenylist', $data['chain_symbol']);
        }



        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        return $this->result;
    }

}