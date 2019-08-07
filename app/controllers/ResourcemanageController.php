<?php

namespace App\Controllers;


use App\Libs\Dun163;

class ResourcemanageController extends ControllerBase
{
    public function getEOSAction()
    {
        $adminSession = $this->getBussiness('Permission')->getSession();

        //查询EOS出账钱包是否存在
        $transactionWallet = $this->getDI()->getShared('db')->query("select address from wallet_".$adminSession['pro_no']."_project_wallets where pro_no='" . $adminSession['pro_no'] . "' and chain_symbol='EOS' and wallet_type=2");
        $transactionWallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $transactionWallet = $transactionWallet->fetch();
        if($transactionWallet){
            $this->view->address = $transactionWallet['address'];
        }

        //获取余额
        $url = $this->config->wallet_ip.'api/getAccountMoney';
        $walletData['coin_type'] = 'EOS';
        $walletData['type'] = 'EOS';
        $walletData['address'] = $transactionWallet['address'];

        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
        if (!isset($reqWallet)) {
            $this->functions->alert('获取余额接口请求失败');
        }

        if($reqWallet['status'] != 1){
            $this->functions->alert($reqWallet['msg']);
        }

        $this->view->account = $reqWallet['data']['EOS']['data'];

        //请求钱包获取资源价格
        $url = $this->config->wallet_ip.'api/getResultsPrice';
        $reqApiData['type'] = 'EOS';
        $reqApiData['account_name'] = $transactionWallet['address'];
        $apiForAddWallet = $this->functions->http_request_forWallet($url,'POST',$reqApiData);
        if (!isset($apiForAddWallet)) {
            $this->functions->alert('获取资源价格接口请求失败');
        }
        if ($apiForAddWallet['status'] != 1) {
            $this->functions->alert($apiForAddWallet['msg']);
        }

        $this->view->resource_price = $apiForAddWallet['data'];

        return $this->view->pick('resource/eos');
    }

    public function buyEOSRamAction(){
        $data['chain_symbol'] = $this->request->getPost('chain_symbol');
        $data['password'] = $this->request->getPost('password');
        $data['ram_num'] = $this->request->getPost('ram_num');

        //校验数据
        $validation = $this->paValidation;
        $validation->buyEOSRam($data);
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $buyRam = $this->getBussiness('ResourceManage')->buyRam($data);
        $this->functions->alert($buyRam['msg']);
    }

    public function buyEOSCpuNetAction(){
        $data['chain_symbol'] = $this->request->getPost('chain_symbol');
        $data['password'] = $this->request->getPost('password');
        $data['cpu_num'] = $this->request->getPost('cpu_num');
        $data['net_num'] = $this->request->getPost('net_num');

        //校验数据
        $validation = $this->paValidation;
        $validation->buyEOSCpuNet($data);
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $buyCpuNet = $this->getBussiness('ResourceManage')->buyCpuNet($data);
        $this->functions->alert($buyCpuNet['msg']);

    }

}

