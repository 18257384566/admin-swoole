<?php
/**
 * Created by PhpStorm.
 * User: Echo
 * Date: 2018/11/3
 * Time: 11:34
 */

namespace App\Controllers;

class ApiController extends ControllerBase{

    /**
     * 为项目方生成secret_key
     */
    public function createUserApiAction(){
        $pro_no = $this->request->getPost('pro_no');
        if (!$pro_no){
            $this->result['status'] = -1;
            $this->result['msg'] = "缺少参数";
            $this->ajaxReturn();
        }

        $array_key = $this->getBussiness("Api")->createUserApi($pro_no);

        $this->result['status'] = 1;
        $this->result['msg'] = "生成成功";
        $this->result['data'] = $array_key;
        $this->ajaxReturn();

    }

    //发起提现请求
    public function withdrawAesAction(){
        $datas = file_get_contents('php://input');
        $datas = $this->getBussiness("Api")->decrypt($datas);
        if (!$datas){
            $this->result['status'] = -1;
            $this->result['msg'] = "非法数据";
            $this->ajaxStringReturn('',400);
        }
        $pro_no = $datas['pro_no'];
        $post_data = $datas['post_data'];
        $reqData = array();
        $reqData['coin_type'] = isset($post_data['coin_type']) ? $post_data['coin_type'] : false;
        $reqData['num'] = isset($post_data['num']) ? $post_data['num'] : false;
        $reqData['address'] = isset($post_data['address']) ? $post_data['address'] : false;
        $reqData['pro_no'] = isset($post_data['pro_no']) ? $post_data['pro_no'] : false;
        $reqData['sign'] = isset($post_data['sign']) ? $post_data['sign'] : false;
        $reqData['withdraw_no'] = isset($post_data['withdraw_no']) ? $post_data['withdraw_no'] : "";
        if(isset($post_data['memo'])){
            $reqData['memo'] = $post_data['memo'];
        }

        if (!is_numeric($reqData['num']) || $reqData['num'] <= 0){
            $this->result['status'] = -1;
            $this->result['msg'] = "数据格式有误";
            $this->ajaxStringReturn($pro_no);
        }

        if (!$reqData['pro_no'] || !$reqData['sign'] || !$reqData['coin_type'] || !$reqData['num'] || !$reqData['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = "缺少参数";
            $this->ajaxStringReturn($pro_no);
        }

        $prefix = $this->config->database['prefix'];
        $this->config->database['prefix'] = $prefix.$reqData['pro_no'].'_';

        $coin = $this->getBussiness('ProjectCoin')->getByWhere($reqData['pro_no'],'coin_symbol',$reqData['coin_type'],"chain_symbol");

        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现币种不存在";
            $this->ajaxReturn();
        }
        $this->config->database['prefix'] = $prefix;

        //出账钱包地址
        $wallet = $this->getDI()->getShared('db')->query("select address from wallet_".$pro_no."_project_wallets where pro_no='" . $pro_no . "' and chain_symbol='" . $coin[0]['chain_symbol'] . "' and wallet_type=2");
        $wallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $wallet = $wallet->fetch();
        if(!$wallet){
            $this->result['status'] = -1;
            $this->result['msg'] = "请先设置出账钱包";
            $this->ajaxStringReturn($pro_no);
        }

        if ($reqData['address'] == $wallet['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现地址不能为本账户";
            $this->ajaxStringReturn($pro_no);
        }

        if (strlen($reqData['withdraw_no']) > 128){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现编号不符合规则";
            $this->ajaxStringReturn($pro_no);
        }

        $this->result = $this->getBussiness("Api")->withdrawAes($reqData);
        $this->ajaxStringReturn($pro_no);
    }


    //发起提现请求
    public function withdrawAction(){
        $reqData['coin_type'] = $this->request->getPost('coin_type');
        $reqData['num'] = $this->request->getPost('num');
        $reqData['address'] = $this->request->getPost('address');
        $reqData['pro_no'] = $this->request->getPost('pro_no');
        $reqData['sign'] = $this->request->getPost('sign');
        $reqData['withdraw_no'] = $this->request->getPost('withdraw_no') ? $this->request->getPost('withdraw_no') : "";
        $reqData['memo'] = $this->request->getPost('memo') ? $this->request->getPost('memo') : "";
        if($this->request->getPost('memo') && !empty($this->request->getPost('memo'))){
            $reqData['memo'] = $this->request->getPost('memo');
        }
        if (!is_numeric($reqData['num']) || $reqData['num'] <= 0){
            $this->result['status'] = -1;
            $this->result['msg'] = "数据格式有误";
            $this->ajaxReturn();
        }
        if (!$reqData['pro_no'] || !$reqData['sign'] || !$reqData['coin_type'] || !$reqData['num'] || !$reqData['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = "缺少参数";
            $this->ajaxReturn();
        }

        $prefix = $this->config->database['prefix'];
        $this->config->database['prefix'] = $prefix.$reqData['pro_no'].'_';

        $coin = $this->getBussiness('ProjectCoin')->getByWhere($reqData['pro_no'],'coin_symbol',$reqData['coin_type'],"chain_symbol");

        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现币种不存在";
            $this->ajaxReturn();
        }
        $this->config->database['prefix'] = $prefix;

        //出账钱包地址
        $wallet = $this->getDI()->getShared('db')->query("select address from wallet_".$reqData['pro_no']."_project_wallets where pro_no='" . $reqData['pro_no'] . "' and chain_symbol='" . $coin[0]['chain_symbol'] . "' and wallet_type=2");
        $wallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $wallet = $wallet->fetch();
        if(!$wallet){
            $this->result['status'] = -1;
            $this->result['msg'] = "请先设置出账钱包";
            $this->ajaxReturn();
        }

        if ($reqData['address'] == $wallet['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现地址不能为本账户";
            $this->ajaxReturn();
        }

        if (strlen($reqData['withdraw_no']) > 128){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现编号不符合规则";
            $this->ajaxReturn();
        }

        $this->result = $this->getBussiness("Api")->withdraw($reqData);
        $this->ajaxReturn();
    }

    public function getSignAction(){
        $reqData['coin_type'] = $this->request->getPost('coin_type');
        $reqData['num'] = $this->request->getPost('num');
        $reqData['address'] = $this->request->getPost('address');
        $reqData['pro_no'] = $this->request->getPost('pro_no');
        $reqData['withdraw_no'] = $this->request->getPost('withdraw_no') ? $this->request->getPost('withdraw_no') : "";
        if($this->request->getPost('memo') && !empty($this->request->getPost('memo'))){
            $reqData['memo'] = $this->request->getPost('memo');
        }
        if (!$reqData['pro_no'] || !$reqData['coin_type'] || !$reqData['num'] || !$reqData['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = "缺少参数";
            $this->ajaxReturn();
        }
        if (strlen($reqData['withdraw_no']) > 128){
            $this->result['status'] = -1;
            $this->result['msg'] = "提现编号不符合规则";
            $this->ajaxReturn();
        }
        $sign = $this->getBussiness("Api")->getSign($reqData);
        $this->result['status'] = 1;
        $this->result['data'] = $sign;
        $this->ajaxReturn();
    }

    public function encryptTestAction(){
        $datas = file_get_contents('php://input');
        $datas = explode(',,,',$datas);
        $pro_no = $datas[1];
        $data = $datas[0];

        $data = json_decode($data,true);
        $sign = $this->getBussiness("Api")->encrypt($pro_no,$data);
        $this->result['status'] = 1;
        $this->result['data'] = $sign;
        $this->ajaxReturn();
    }

    public function decryptAesAction(){
        $data = file_get_contents('php://input');
        $dd = $this->getBussiness("Api")->decrypt($data);
        if (!$dd){
            $this->result['status'] = -1;
            $this->result['msg'] = "非法数据";
//            $this->ajaxReturn();
            $this->ajaxStringReturn('',400);
        }
        $pro_no = $dd['pro_no'];
        $sign = $dd['post_data'];
        $this->result['status'] = 1;
        $this->result['data'] = $sign;
//        $this->ajaxReturn();
        $this->ajaxStringReturn($pro_no);

    }

    public function decryptTestAction(){
        $data = file_get_contents('php://input');
        $dd = $this->getBussiness("Api")->decrypt($data);
        if (!$dd){
            $this->result['status'] = -1;
            $this->result['msg'] = "非法数据";
            $this->ajaxReturn();
            $this->ajaxStringReturn('',400);
        }
        $pro_no = $dd['pro_no'];
        $sign = $dd['post_data'];
        $this->result['status'] = 1;
        $this->result['data'] = $sign;
        $this->ajaxReturn();
        $this->ajaxStringReturn($pro_no);

    }

    /**
     * ajax json 输出
     * array $result
     * 一般由个的元素组成'code','msg','data'
     */
    protected function ajaxStringReturn($pro_no,$code = 200){
        if ($code !== 200){
            $this->response->setStatusCode($code,"An error occurred");
            $this->response->setContent("Sorry, An error occurred, please check the data");
            $this->response->send();
            $this->view->disable();
            exit();
        }
        $data = $this->getBussiness("Api")->encrypt($pro_no,$this->result);
        $this->response->setContent($data);
        $this->response->send();
        $this->view->disable();
        exit();
    }

}