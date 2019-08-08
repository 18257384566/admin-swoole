<?php

namespace App\Controllers;
use Phalcon\Mvc\Model\Query;


class TestController extends ControllerBase
{
    //添加项目数据表
    public function addtableAction(){
        $param = $this->dispatcher->getParam('pro_no');
        if(strpos($param,'AddTable') === false){
            return false;
        }else{
            $pro_no = substr($param,0,-8);
        }
        $this->getBussiness('Table')->add($pro_no);
    }

    public function noticeAction(){
        $data['send_type'] = $this->request->getPost('send_type');
        $data['status'] = $this->request->getPost('status');
        $data['from_address'] = $this->request->getPost('from_address');
        $data['to_address'] = $this->request->getPost('to_address');
        $data['num'] = $this->request->getPost('num');
        $data['hash'] = $this->request->getPost('hash');
        $data['fee'] = $this->request->getPost('fee');
        $data['coin_type'] = $this->request->getPost('coin_type');
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['order_no'] = $this->request->getPost('order_no');
        $this->getBussiness('Api')->sendHashChange($data);
    }


    //添加项目
    public function addprojectAction(){
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['pro_name'] = $this->request->getPost('pro_name');
        $data['contacts_name'] = $this->request->getPost('contacts_name');//联系人
        $data['contacts_phone'] = $this->request->getPost('contacts_phone');
        $data['corporate_name'] = $this->request->getPost('corporate_name');//公司全称
        $data['security_url'] = $this->request->getPost('security_url');
        $data['encryption_security_url'] = $this->request->getPost('encryption_security_url');
        $data['project_hash_url'] = $this->request->getPost('project_hash_url');
        $data['project_wallet_url'] = $this->request->getPost('project_wallet_url');
        $data['login_url'] = $this->request->getPost('login_url');
        $data['created_at'] = $data['updated_at'] = time();
        //校验数据
        $validation = $this->paValidation;
        $validation->addproject();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $result = $this->getBussiness('Project')->add($data);
        return json_encode($result);

    }


    //添加管理员
    public function addadminAction(){
        $data['admin_name'] = $this->request->getPost('admin_name');
        $data['real_name'] = $this->request->getPost('real_name');
        $data['password'] = strtolower($this->request->getPost('password'));
        $data['phone'] = $this->request->getPost('phone');
        $data['is_super'] = $this->request->getPost('is_super');
        $data['is_power'] = $this->request->getPost('is_power');
        $data['permissions'] = $this->request->getPost('permissions');


        //校验数据
        $validation = $this->paValidation;
        $validation->addAdmin();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $pro_no = $this->request->getPost('pro_no');

        $result = $this->getBussiness('Admin')->addFornewproject($pro_no,$data);
        return json_encode($result);

    }

    //添加公链
    public function addchainAction(){
        $data['chain_name'] = $this->request->getPost('chain_name');
        $data['chain_symbol'] = $this->request->getPost('chain_symbol');
        $data['chain_intro'] = $this->request->getPost('chain_intro');
        $data['publish_date'] = $this->request->getPost('publish_date');
        $data['issuance_total'] = $this->request->getPost('issuance_total');
        $data['circulate_total'] = $this->request->getPost('circulate_total');
        $data['initial_price'] = $this->request->getPost('initial_price');
        $data['white_paper'] = $this->request->getPost('white_paper');
        $data['website'] = $this->request->getPost('website');
        $data['blockchain'] = $this->request->getPost('blockchain');
        $data['ip'] = $this->request->getPost('ip');
        $data['created_at'] = $data['updated_at'] = time();


        //校验数据
        $validation = $this->paValidation;
        $validation->addchain();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $result = $this->getBussiness('SystemChain')->add($data);
        return json_encode($result);

    }

    //生成订单编号
    private function createWithOrderNum($coin_name){
        $time = time();
        $uniqueString = $this->functions->uniqueString(8);
        return "MolecularWallet_".$coin_name.$time.$uniqueString;

    }

    //查询流水 增加充值订单
    public function addDepositOrderAction(){
        $pro_no = $this->dispatcher->getParam('pro_no');
        $table = 'wallet_'.$pro_no.'_project_wallets_flow';
        $list=$this->db->query("select id,pro_no,pro_name,from_address,to_address,status,hash,chain_symbol,chain_id,coin_amount,flow_type,coin_symbol,coin_id,created_at,updated_at from ".$table." where flow_type=1");
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();
        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($pro_no.':addDepositOrder',3600*24);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单不能重复操作';
            return json_encode($this->result);
        }
        foreach ($list as $v){
            $data['pro_no'] = $v['pro_no'];
            $data['pro_name'] = $v['pro_name'];
            $data['deposit_no'] = $this->createWithOrderNum($v['coin_symbol']);
            $data['address'] = $v['to_address'];
            $data['chain_symbol'] = $v['chain_symbol'];
            $data['chain_id'] = $v['chain_id'];
            $data['coin_symbol'] = $v['coin_symbol'];
            $data['coin_id'] = $v['coin_id'];
            $data['coin_amount'] = $v['coin_amount'];
            $data['created_at'] = $v['created_at'];
            $data['updated_at'] = $v['updated_at'];
            $this->getBussiness('Deposit')->add($data);
        }


    }

    //更新全权管理员redis
    public function updatePowerAdminAction(){
        $admins = $this->getDI()->getShared('db')->query("select * from wallet_system_project_admin ");
        $admins->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $admins = $admins->fetchAll();
        foreach ($admins as $v){
            //redis
            $redisData = [
                'id'=>$v['id'],
                'account'=>$v['admin_name'],
                'permissions'=>$v['permissions'],
                'is_power'=>$v['is_power'],
                'is_super'=>$v['is_super'],
            ];

            $key = $v['pro_no'].":".$v['admin_no'];
            $this->getBussiness('RedisCache')->createAdmin($key,$redisData);

        }

    }

    //修改全权管理员信息
//    public function editPowerAdminAction(){
//        $data['pro_no'] = $this->request->getPost('pro_no');
//        $data['admin_name'] = $this->request->getPost('admin_name');
//        $data['is_power'] = $this->request->getPost('is_power');
//        $data['is_super'] = $this->request->getPost('is_super');
//
//        //校验数据
//        $validation = $this->paValidation;
//        $validation->editpoweradmin();
//        $messages = $validation->validate($data);
//        if(count($messages)){
//            $message = $messages[0]->getMessage();
//            $this->result['status'] = -1;
//            $this->result['msg'] = $message;
//            return json_encode($this->result);
//        }
//
//        $result = $this->getBussiness('Admin')->editPowerAdmin($data);
//        return json_encode($result);
//    }

}