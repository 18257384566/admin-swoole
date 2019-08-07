<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class UnknowncoinController extends ControllerBase
{
    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'wallet_transfer_unknowncoin',
            'show_name'=>'转出未添加币种管理',
            'top_id'=>0,
        ];

        return $this->getBussiness('Permission')->add($data);

    }

    public function transferViewAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'transfer_unknowncoin',
            'show_name'=>'转出未添加币种管理',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'transfer_unknowncoin_do',
            'show_name'=>'可操作',
            'top_id'=>$second_top_id,
        ];
        $do_id = $this->getBussiness('Permission')->add($datas);

        $adminPermission = $this->getBussiness('Permission')->getPermission($data['name']);
        //将权限传人视图
        $data['is_super'] = $adminPermission['is_super'];$data['is_power'] = $adminPermission['is_power'];
        $data['permission'] = $adminPermission['permissions'];
        $data['do'] = $do_id;
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        $this->view->pick('unknowncoin/transfer');
    }

    public function selectOrderAction(){
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['token_contract'] = $this->request->getPost('token_contract');
        $data['abi'] = $this->request->getPost('abi');
        $data['deposit_address'] = $this->request->getPost('deposit_address');
        $data['hash'] = $this->request->getPost('hash');
        //校验数据
        $validation = $this->paValidation;
        $validation->selectOrder();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $result = $this->getBussiness('UnknownCoin')->selectOrder($data);
        if($result['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $result['msg'];
            return json_encode($this->result);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = $result['msg'];
        $this->result['data'] = $result['data'];
        return json_encode($this->result);
    }

    public function transferAction(){
        $pro_no = $this->request->getPost('pro_no');
        $data['token_contract'] = $this->request->getPost('token_contract');
        $data['abi'] = $this->request->getPost('abi');
        $data['address'] = $this->request->getPost('address');
        $data['batch_no'] = $this->request->getPost('batch_no');
        $data['coin_type'] = $this->request->getPost('coin_type');
        $data['num'] = $this->request->getPost('num');
        $data['password1'] = $this->request->getPost('password1');
        $data['password2'] = $this->request->getPost('password2');
        $data['password3'] = $this->request->getPost('password3');
        $data['decimals'] = $this->request->getPost('decimals');

        //校验数据
        $validation = $this->paValidation;
        $validation->transferColdWallet();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $data['password1'] = strtolower($data['password1']);
        $data['password2'] = strtolower($data['password2']);
        $data['password3'] = strtolower($data['password3']);

        $transfer = $this->getBussiness('UnknownCoin')->transfer($data);

//        if($transfer['status'] == -2){
//            $this->functions->alert($transfer['msg'],'/'.$pro_no.'/logout');
//        }elseif($transfer['status'] == -3){
//            $this->functions->alert($transfer['msg'],'/'.$pro_no.'/project/detail');
//        }elseif($transfer['status'] == -1){
//            $this->functions->alert($transfer['msg']);
//        }

        $this->result['status'] = $transfer['status'];
        $this->result['msg'] = $transfer['msg'];
        $this->result['data'] = $transfer['data'];
        return json_encode($this->result);

    }

    //钱包通知后台已完成转出
//    public function finishTransferAction(){
//        //获取参数
//        $data['pro_no'] = $this->request->getPost('pro_no');
//        $data['type'] = $this->request->getPost('type');
//        $data['coin_type'] = $this->request->getPost('coin_type');
//        $data['hash'] = $this->request->getPost('hash');
//        $data['address'] = $this->request->getPost('address');//用户地址
//        $data['from_address'] = $this->request->getPost('from_address');//用户地址
//        $data['leng'] = $this->request->getPost('leng');//冷钱包地址
//        $data['num'] = $this->request->getPost('num');
//        $data['fee'] = $this->request->getPost('fee');
//        $data['status'] = $this->request->getPost('status');
//
//        //校验数据
//        $validation = $this->paValidation;
//        $validation->finishUnknownCoinTransfer();
//        $messages = $validation->validate($data);
//
//        //如果存在错误信息
//        if (count($messages)) {
//            $message = $messages[0]->getMessage();
//            $this->result['status'] = -1;
//            $this->result['msg'] = $message;
//            return json_encode($this->result);
//        }
//
//        $transfer = $this->getBussiness('UnknownCoin')->finishTransfer($data);
//        return json_encode($transfer);
//
//
//
//
//    }

}