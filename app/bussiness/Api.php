<?php
/**
 * Created by PhpStorm.
 * User: Echo
 * Date: 2018/11/7
 * Time: 09:54
 */

namespace App\Bussiness;

use App\Libs\ApiEncrypt;

class Api extends BaseBussiness{

    //生成项目secret_key
    public function createUserApi($pro_no){
        $filed = 'id,pro_no,secret_key,encryption_security_url';
        $pro_no_info = $this->getModel('SystemProjectInfo')->getByProNo($pro_no,$filed);
        if (!$pro_no_info){
            $this->result['status'] = -1;
            $this->result['msg'] = "项目不存在";
            $this->ajaxReturn();
        }
        if ($pro_no_info['secret_key'] && $pro_no_info['encryption_security_url']){
            $this->result['status'] = -1;
            $this->result['msg'] = "相关参数均已生成,请勿反复生成";
            $this->ajaxReturn();
        }


        if (!$pro_no_info['secret_key']){
            $secret_key = $this->functions->uniqueString(16);

            $data = array(
                'secret_key' => $secret_key
            );

            $update_status = $this->getModel('SystemProjectInfo')->updateById($pro_no_info['id'],$data);
            if (!$update_status){
                $this->result['status'] = -1;
                $this->result['msg'] = "secret_key生成失败,请重新尝试";
                $this->ajaxReturn();
            }
        }else{
            $secret_key = $pro_no_info['secret_key'];
        }

        if (!$pro_no_info['encryption_security_url']){
            $encryption_security_url = $this->functions->uniqueString(16);

            $data = array(
                'encryption_security_url' => $encryption_security_url
            );

            $update_status = $this->getModel('SystemProjectInfo')->updateById($pro_no_info['id'],$data);
            if (!$update_status){
                $this->result['status'] = -1;
                $this->result['msg'] = "encryption_security_url生成失败,请重新尝试";
                $this->ajaxReturn();
            }
        }else{
            $encryption_security_url = $pro_no_info['encryption_security_url'];
        }

        return ['secret_key'=>$secret_key,'encryption_security_url'=>$encryption_security_url];


    }


    //提现申请
    public function withdrawAes($reqData){
        $this->config->database['prefix'] = $this->config->database['prefix'].$reqData['pro_no'].'_';
        $project_info = $this->getModel("SystemProjectInfo")->getByProNo($reqData['pro_no'],'pro_name,secret_key');
        if (!$project_info){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否存在或接口秘钥是否设置";
            return  $this->result;
        }
        $sign = $this->MakeSign($project_info['secret_key'],$reqData);

        if ($sign !== $reqData['sign']){
            $this->result['status'] = -1;
            $this->result['msg'] = "非法请求";
            return  $this->result;
        }
        $reqData['coin_type'] = strtoupper($reqData['coin_type']);


        //验证该项目是否添加过要转账的币种,并拿到币种所属公链
        $project_coin = $this->getChainSymbol($reqData['coin_type'],$reqData['pro_no']);
        if (!$project_coin){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否添加过该币种";
            $this->ajaxStringReturn($reqData['pro_no']);
        }
        $walletData = array();
        $walletData['type'] = $project_coin['chain_symbol'];

        //验证地址格式
        $walletData['address'] = $reqData['address'];
        $url = $this->config->wallet_ip.'api/validationAddress';
        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
        if (!isset($reqWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '提交失败,请重新提交';
            return  $this->result;
        }

        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = "转账地址格式不正确";
            return  $this->result;
        }

        $this->checkToAddress($reqData['pro_no'],$walletData['type'],$reqData['address'],true);

        $data = array();

        $data['pro_name']   = $project_info['pro_name'];
        $data['status'] = 0;
        $data['pro_no'] = $reqData['pro_no'];
        if ($reqData['withdraw_no'] != ""){
            $data['withdraw_no'] = $reqData['withdraw_no'];
            $order_status = $this->checkWithdrawNo($data['withdraw_no'],$data['pro_no']);
            //检查提现编号是否已经存在
            if (!empty($order_status)){
                $this->result['status'] = '-1';
                $this->result['msg'] = '订单已存在,订单创建失败';
                return  $this->result;
            }
        }else{
            $data['withdraw_no'] = $this->createWithOrderNum($reqData['coin_type']);
        }
        $data['address'] = $reqData['address'];
        $data['chain_symbol'] = $project_coin['chain_symbol'];
        $data['chain_id'] = $project_coin['chain_id'];
        $data['coin_symbol'] = $project_coin['coin_symbol'];
        $data['coin_id'] = $project_coin['id'];
        $data['coin_amount'] = $reqData['num'];
        $data['memo'] = isset($reqData['memo']) ? $reqData['memo'] : "";
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $result = $this->getModel("ProjectWithdraw")->createWithdraw($data);
        if(!$result){
            $this->result['status'] = '-1';
            $this->result['msg'] = '订单创建失败';
            return  $this->result;
        }else{
            $this->result['status'] = 1;
            $this->result['msg'] = '订单创建成功';
            $this->result['data'] = $data['withdraw_no'];
            return $this->result;
        }

    }


    //提现申请
    public function withdraw($reqData){
        $this->config->database['prefix'] = $this->config->database['prefix'].$reqData['pro_no'].'_';
        $project_info = $this->getModel("SystemProjectInfo")->getByProNo($reqData['pro_no'],'pro_name,secret_key');
        if (!$project_info){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否存在或接口秘钥是否设置";
            return  $this->result;
        }
        $sign = $this->MakeSign($project_info['secret_key'],$reqData);

        if ($sign !== $reqData['sign']){
            $this->result['status'] = -1;
            $this->result['msg'] = "非法请求";
            return  $this->result;
        }
        $reqData['coin_type'] = strtoupper($reqData['coin_type']);


        //验证该项目是否添加过要转账的币种,并拿到币种所属公链
        $project_coin = $this->getChainSymbol($reqData['coin_type'],$reqData['pro_no']);
        if (!$project_coin){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否添加过该币种";
            $this->ajaxReturn();
        }
        $walletData = array();
        $walletData['type'] = $project_coin['chain_symbol'];

        //验证地址格式
        $walletData['address'] = $reqData['address'];
        $url = $this->config->wallet_ip.'api/validationAddress';
        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
        if (!isset($reqWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '提交失败,请重新提交';
            return  $this->result;
        }

        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = "转账地址格式不正确";
            return  $this->result;
        }

        $this->checkToAddress($reqData['pro_no'],$walletData['type'],$reqData['address']);

        $data = array();

        $data['pro_name']   = $project_info['pro_name'];
        $data['status'] = 0;
        $data['pro_no'] = $reqData['pro_no'];
        if ($reqData['withdraw_no'] != ""){
            $data['withdraw_no'] = $reqData['withdraw_no'];
            $order_status = $this->checkWithdrawNo($data['withdraw_no'],$data['pro_no']);
            //检查提现编号是否已经存在
            if (!empty($order_status)){
                $this->result['status'] = '-1';
                $this->result['msg'] = '订单已存在,订单创建失败';
                return  $this->result;
            }
        }else{
            $data['withdraw_no'] = $this->createWithOrderNum($reqData['coin_type']);
        }
        $data['address'] = $reqData['address'];
        $data['chain_symbol'] = $project_coin['chain_symbol'];
        $data['chain_id'] = $project_coin['chain_id'];
        $data['coin_symbol'] = $project_coin['coin_symbol'];
        $data['coin_id'] = $project_coin['id'];
        $data['coin_amount'] = $reqData['num'];
        $data['memo'] = isset($reqData['memo']) ? $reqData['memo'] : "";
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $result = $this->getModel("ProjectWithdraw")->createWithdraw($data);
        if(!$result){
            $this->result['status'] = '-1';
            $this->result['msg'] = '订单创建失败';
            return  $this->result;
        }else{
            $this->result['status'] = 1;
            $this->result['msg'] = '订单创建成功';
            $this->result['data'] = $data['withdraw_no'];
            return $this->result;
        }

    }


    //发送钱包地址
    public function sendAccountInfo($reqData){
        $pro_no = $reqData['pro_no'];
        $project_info = $this->getModel("SystemProjectInfo")->getByProNo($reqData['pro_no'],'project_wallet_url,secret_key');
        if (!$project_info || !$project_info['secret_key'] || !$project_info['project_wallet_url'] || !$reqData['type']){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否存在或接口秘钥和通知地址是否设置";
            $this->ajaxReturn();
        }
        $data = array();
        $data['pro_no'] = $pro_no;
        $data['accounts'] = $reqData['accounts'];
        $data['type'] = $reqData['type'];
        $data['batch_num'] = $reqData['batch_num'];
        $sign = $this->MakeSign($project_info['secret_key'],$data);
        $data['sign'] = $sign;

        $url = $project_info['project_wallet_url'];
        if (strpos($url,"Aes")){
            $send_status = $this->curl->httpPost($url,$this->encrypt($pro_no,$data));
        }else{
            $send_status = $this->functions->http_request_forWallet($url,'POST',$data);
        }
        file_put_contents(BASE_PATH."/address_notice.log","url:".$url.",data:".json_encode($data,true).",response:".json_encode($send_status,true).PHP_EOL,FILE_APPEND);
        $this->result['status'] = 1;
        $this->result['msg'] = '地址通知成功';
        $this->result['data'] = $send_status;
        return $this->result;

        //成功失败处理

    }


    //充值提现状态通知
    public function sendHashChange($reqData){
        $pro_no = $reqData['pro_no'];
        $project_info = $this->getModel("SystemProjectInfo")->getByProNo($reqData['pro_no'],'project_hash_url,secret_key');
        if (!$project_info || !$project_info['secret_key'] || !$project_info['project_hash_url']){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否存在或接口秘钥和通知地址是否设置";
            $this->ajaxReturn();
        }

        if ($reqData['send_type'] == "transaction"){
            //验证该项目是否添加过要转账的币种,是否被禁用,并拿到币种所属公链
            $project_coin = $this->getChainSymbol($reqData['coin_type'],$pro_no,true);
            if (!$project_coin){
                $this->result['status'] = -1;
                $this->result['msg'] = "请检查项目是否添加过该币种,或该币种是否被禁用";
                $this->ajaxReturn();
            }
        }


        $data = array();
        $data['pro_no'] = $pro_no;
//        $data['message'] = isset($reqData['message']) ?? "";
        $data['status'] = $reqData['status'];
        $data['send_type'] = $reqData['send_type'];
        $data['from_address'] = $reqData['from_address'];
        $data['to_address'] = $reqData['to_address'];
        $data['num'] = $reqData['num'];
        $data['hash'] = $reqData['hash'];
        $data['fee'] = $reqData['fee'];
        $data['coin_type'] = $reqData['coin_type'];
        $data['order_no'] = $reqData['order_no'];
        $sign = $this->MakeSign($project_info['secret_key'],$data);
        $data['sign'] = $sign;

        $url = $project_info['project_hash_url'];
        if (strpos($url,"Aes")){
            $send_status = $this->curl->httpPost($url,$this->encrypt($pro_no,$data));
        }else{
            $send_status = $this->functions->http_request_forWallet($url,'POST',$data);
        }
        file_put_contents(BASE_PATH."/notice.log",json_encode($data,true).PHP_EOL,FILE_APPEND);
        file_put_contents(BASE_PATH."/notice.log","send_status:".json_encode($send_status,true).PHP_EOL,FILE_APPEND);

        //成功失败处理

    }


    //检查项目方是否添加该币种,已添加返回该币种所属公链,未添加返回false
    public function getChainSymbol($coin_name,$pro_no,$status = false){
        if ($status){
            $res = $this->getModel("ProjectCoin")->getByWheres(array("coin_symbol","pro_no","status"),array($coin_name,$pro_no,1),"id,chain_id,coin_symbol,chain_symbol");
        }else{
            $res = $this->getModel("ProjectCoin")->getByWheres(array("coin_symbol","pro_no"),array($coin_name,$pro_no),"id,chain_id,coin_symbol,chain_symbol");
        }
        if (!$res){
            return false;
        }
        return $res;

    }

    //检查到账地址是否为出账钱包地址
    private function checkToAddress($pro_no,$chain_symbol,$to_address,$aes = false){
        $res = $this->getModel("ProjectWallet")->getProWallet($pro_no,$chain_symbol,2,"id,address");
        if (!$res){
            $this->result['status'] = -1;
            $this->result['msg'] = '提交失败,请检查是否设置出账钱包地址';
            if ($aes){
                $this->ajaxStringReturn($pro_no);
            }else{
                $this->ajaxReturn();
            }


        }
        if ($to_address == $res['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = '提现到账地址不能为平台设置的出账钱包地址';
            if ($aes){
                $this->ajaxStringReturn($pro_no);
            }else{
                $this->ajaxReturn();
            }

        }
        return true;
    }

    //生成订单编号
    private function createWithOrderNum($coin_name){
        $time = time();
        $uniqueString = $this->functions->uniqueString(8);
        return "MolecularWallet_".$coin_name.$time.$uniqueString;

    }


    public function getSign($reqData){
        $secret_key = $this->getModel("SystemProjectInfo")->getByProNo($reqData['pro_no'],'secret_key');
        if (!$secret_key){
            $this->result['status'] = -1;
            $this->result['msg'] = "请检查项目是否存在或接口秘钥是否设置";
            $this->ajaxReturn();
        }
        $sign = $this->MakeSign($secret_key['secret_key'],$reqData);
        return $sign;
    }

    //检查提现编号是否存在
    private function checkWithdrawNo($withdraw_no,$pro_no){
        $field = "id";
        $withdraw_no_id = $this->getModel("ProjectWithdraw")->getWithdraw($withdraw_no,$pro_no,$field);
        return $withdraw_no_id;
    }


    /**
     * 生成签名
     * @param $secret_key   秘钥
     * @param $values       要参与签名的数组
     * @return string
     */
    private function MakeSign($secret_key, $values)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->ToUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . "&secret_key=".$secret_key;
        //签名步骤三：MD5加密或者HMAC-SHA256
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($values)
    {
        $buff = "";
        foreach ($values as $k => $v)
        {
            if($k != "sign" && trim($v) != ""){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 数据加密
     * @param $pro_no
     * @param $plaintext
     * @return bool|string
     */
    public function encrypt($pro_no,$plaintext){
        if (!$pro_no || !is_array($plaintext)){
            return false;
        }
        $plaintext['wallet_time'] = (string)time();
        $plaintext = json_encode($plaintext,true);
        $project_info = $this->getModel("SystemProjectInfo")->getByProNo($pro_no,'pro_name,encryption_security_url');
        if (!$project_info){
            return false;
        }
        $key = $project_info['encryption_security_url'];
        $api_encrypt = new ApiEncrypt('AES-256-CBC',$key,OPENSSL_RAW_DATA,"0000000000000000");
        $en = $api_encrypt->encrypt($plaintext);
        return $en;
    }


    /**
     * 数据解密
     * @param $ciphertext
     * @return array|bool
     */
    public function decrypt($ciphertext){
        $time = time();
        $datas = explode(',',$ciphertext);
        if (count($datas) !== 2 || !isset($datas[1]) || !isset($datas[0])){
            return false;
        }
        $pro_no = $datas[1];
        $ciphertext = $datas[0];
        $project_info = $this->getModel("SystemProjectInfo")->getByProNo($pro_no,'pro_name,encryption_security_url');
        if (!$project_info){
            return false;
        }
//        var_dump($project_info);
        $key = $project_info['encryption_security_url'];
        $api_decrypt = new ApiEncrypt('AES-256-CBC',$key,OPENSSL_RAW_DATA,"0000000000000000");
        $de = $api_decrypt->decrypt($ciphertext);
//        var_dump($ciphertext);
//        var_dump($de);
        $de = json_decode($de,true);
        if (!isset($de['wallet_time'])){
            $this->result['status'] = -1;
            $this->result['msg'] = "缺少参数";
            $this->ajaxStringReturn($pro_no);
        }
        if ($de['wallet_time'] > $time || $time - $de['wallet_time'] > 30){
            $this->result['status'] = -1;
            $this->result['msg'] = "数据过期";
            $this->ajaxStringReturn($pro_no);
        }
        return ['pro_no'=>$pro_no,'post_data'=>$de];
    }

    /**
     * ajax返回加密后的数据
     * @param $pro_no
     * @param int $code
     */
    protected function ajaxStringReturn($pro_no,$code = 200){
        if ($code !== 200){
            $this->response->setStatusCode($code,"Not Found");
            $this->response->setContent("Sorry, the page doesn't exist");
            $this->response->send();
            $this->view->disable();
            exit();
        }
        $data = $this->encrypt($pro_no,$this->result);
        $this->response->setContent($data);
        $this->response->send();
        $this->view->disable();
        exit();
    }
}