<?php

namespace App\Core;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Between;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\StringLength;

class PaValidation extends Validation
{
    public function initialize()
    {

    }

    //管理员登陆
    public function doLogin(){
        $this->add('name',new PresenceOf(array(
            'message' => '请输入账户名',
            'cancelOnFail' => true,
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请输入密码',
            'cancelOnFail' => true
        )));
    }

    public function addAdmin(){
        $this->add('admin_name', new PresenceOf(array(
            'message' => '请填写账户名',
            'cancelOnFail' => true
        )));
        $this->add('admin_name', new StringLength(array(
            'max' => 20,
            'messageMaximum' => '账户名不超过20个字',
            'cancelOnFail' => true
        )));
        $this->add('real_name', new PresenceOf(array(
            'message' => '请填写真实姓名',
            'cancelOnFail' => true
        )));
        $this->add('real_name', new StringLength(array(
            'max' => 20,
            'messageMaximum' => '真实姓名不超过20个字',
            'cancelOnFail' => true
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请填写密码',
            'cancelOnFail' => true
        )));
        $this->add('password', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{8,16}$/',
            'message' => '请设置包含英文及数字的8～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
        $this->add('phone', new PresenceOf(array(
            'message' => '请填写手机号',
            'cancelOnFail' => true
        )));
        $this->add('phone', new Regex(array(
            //以非0打头的正整数
            'pattern' => '/^1[3456789]\d{9}$/',
            'message' => '手机号格式不正确',                 //手机号非法
            'cancelOnFail' => true
        )));
        $this->add('role', new PresenceOf(array(
            'message' => '请选择角色',
            'cancelOnFail' => true
        )));
    }

    public function exchange(){
        $this->add('exchange_code', new PresenceOf(array(
            'exchange_code' => '兑换券编号不能唯空',
            'cancelOnFail' => true
        )));
        $this->add('zones', new PresenceOf(array(
            'message' => 'zones不能为空',
            'cancelOnFail' => true
        )));
        $this->add('user_name', new PresenceOf(array(
            'message' => '用户昵称不能为空',
            'cancelOnFail' => true
        )));
    }















    //获取登陆验证码
    public function getLoginCode(){
        //验证手机号是否为空
        $this->add('phone',new PresenceOf(array(
            'message' => '请输入手机号',
            'cancelOnFail' => true,
        )));

        $this->add('phone', new Regex(array(
            //以非0打头的正整数
            'pattern' => '/^1[3456789]\d{9}$/',
            'message' => '手机号格式不正确',                 //手机号非法
            'cancelOnFail' => true
        )));
    }



    public function editAdmin(){
        $this->add('admin_name', new PresenceOf(array(
            'message' => '请填写账户名',
            'cancelOnFail' => true
        )));
        $this->add('real_name', new PresenceOf(array(
            'message' => '请填写真实姓名',
            'cancelOnFail' => true
        )));
//        $this->add('password', new Regex(array(
//            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{8,16}$/',
//            'message' => '请设置包含英文及数字的8～16位字符密码', //密码格式错误
//            'cancelOnFail' => true
//        )));
        $this->add('phone', new PresenceOf(array(
            'message' => '请填写手机号',
            'cancelOnFail' => true
        )));
        $this->add('phone', new Regex(array(
            //以非0打头的正整数
            'pattern' => '/^1[3456789]\d{9}$/',
            'message' => '手机号格式不正确',                 //手机号非法
            'cancelOnFail' => true
        )));
    }

//    public function editpoweradmin(){
//        $this->add('pro_no', new PresenceOf(array(
//            'message' => '请填写项目编号',
//            'cancelOnFail' => true
//        )));
//        $this->add('admin_name', new PresenceOf(array(
//            'message' => '请填写账户名',
//            'cancelOnFail' => true
//        )));
//        $this->add('is_power', new PresenceOf(array(
//            'message' => '请选择是否是全权管理员',
//            'cancelOnFail' => true
//        )));
//        $this->add('is_super', new PresenceOf(array(
//            'message' => '请选择是否是超级管理员',
//            'cancelOnFail' => true
//        )));
//    }

    public function addlengWallet(){
        $this->add('chain_id', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => '请填写冷钱包地址',
            'cancelOnFail' => true
        )));
    }

    public function editlengWallet(){
        $this->add('address', new PresenceOf(array(
            'message' => '请填写冷钱包地址',
            'cancelOnFail' => true
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请填写账号密码',
            'cancelOnFail' => true
        )));
    }

    //新增钱包类型-coin
    public function addWallettype(){
        $this->add('token_contract', new PresenceOf(array(
            'message' => '请填写合约地址',
            'cancelOnFail' => true
        )));
        $this->add('coin_abi', new PresenceOf(array(
            'message' => '请填写ABI',
            'cancelOnFail' => true
        )));
        $this->add('transfer_min', new PresenceOf(array(
            'message' => '请填写转入最小值',
            'cancelOnFail' => true
        )));
        $this->add('transfer_min', new Regex(array(
            //是否为数字
            'pattern' => '/^([0-9]|([0-9]+.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*.[0-9]+)|([0-9]*[1-9][0-9]*))$/',
            'message' => '转入最小值格式不正确',
            'cancelOnFail' => true
        )));

    }

    public function editWallettype(){
        $this->add('transfer_min', new PresenceOf(array(
            'message' => '请填写转入最小值',
            'cancelOnFail' => true
        )));
        $this->add('transfer_min', new Regex(array(
            //是否为数字
            'pattern' => '/^([0-9]|([0-9]+.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*.[0-9]+)|([0-9]*[1-9][0-9]*))$/',
            'message' => '转入最小值格式不正确',
            'cancelOnFail' => true
        )));
    }


    //新增钱包(出账钱包，手续费钱包)
    public function addWallet(){
        $this->add('chain_id', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请填写密码',
            'cancelOnFail' => true
        )));
        $this->add('password', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![a-z]+$)[a-z0-9]{8,20}$/',
            'message' => '请设置包含小写英文与数字组成的8~20位的字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
    }

    public function addEOSWallet(){
        $this->add('chain_id', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => '请填写账户名',
            'cancelOnFail' => true
        )));
        $this->add('address', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![a-z]+$)[a-z1-5]{12}$/',
            'message' => '请设置包含小写英文与1至5的数字组成的12位的字符账户名', //密码格式错误
            'cancelOnFail' => true
        )));
    }

    public function passwordConfirm(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => 'address is null',
            'cancelOnFail' => true
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请填写密码',
            'cancelOnFail' => true
        )));
    }

    //生成用户钱包地址
    public function addWalletaddress($data){
        $this->add('chain_id', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => '请选择公链',
            'cancelOnFail' => true
        )));
        $this->add('count', new PresenceOf(array(
            'message' => '请填写数量',
            'cancelOnFail' => true
        )));
        //测试，暂时注释
        $this->add('count', new Between(array(
            'minimum' => 1,
            'maximum' => 500,
            'message' => '单次生成的数量为1~500',
            'cancelOnFail' => true
        )));
        $this->add('count', new Regex(array(
            //以非0打头的正整数
            'pattern' => '/^[1-9][0-9]*$/',
            'message' => '数量只能填正整数',                 //id非法
            'cancelOnFail' => true
        )));

        //密码
        $this->add('password1', new PresenceOf(array(
            'message' => '请填写第一段密码',
            'cancelOnFail' => true
        )));
        $this->add('password2', new PresenceOf(array(
            'message' => '请填写第二段密码',
            'cancelOnFail' => true
        )));
        $this->add('password3', new PresenceOf(array(
            'message' => '请填写第三段密码',
            'cancelOnFail' => true
        )));

        if($data['chain_symbol'] == 'NULS'){
            $this->add('password1', new Regex(array(
                'pattern' => '/^[a-z0-9]+$/',
                'message' => 'NULS三段密码的长度相加为8~20位,并且密码只能由数字和小写英文组成,请按此操作', //密码格式错误
                'cancelOnFail' => true
            )));
            $this->add('password2', new Regex(array(
                'pattern' => '/^[a-z0-9]+$/',
                'message' => 'NULS三段密码的长度相加为8~20位,并且密码只能由数字和小写英文组成,请按此操作', //密码格式错误
                'cancelOnFail' => true
            )));
            $this->add('password3', new Regex(array(
                'pattern' => '/^[a-z0-9]+$/',
                'message' => 'NULS三段密码的长度相加为8~20位,并且密码只能由数字和小写英文组成,请按此操作', //密码格式错误
                'cancelOnFail' => true
            )));

            $this->add('length', new Between(array(
                'minimum' => 8,
                'maximum' => 20,
                'message' => 'NULS三段密码的长度相加为8~20位,并且密码只能由数字和小写英文组成,请按此操作',
                'cancelOnFail' => true
            )));
        }else{
            $this->add('password1', new Regex(array(
                'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
                'message' => '第一段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
                'cancelOnFail' => true
            )));

            $this->add('password2', new Regex(array(
                'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
                'message' => '第二段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
                'cancelOnFail' => true
            )));

            $this->add('password3', new Regex(array(
                'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
                'message' => '第三段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
                'cancelOnFail' => true
            )));
        }

        $this->add('password_prompt', new StringLength(array(
            'max' => 30,
            'messageMaximum' => '密码提示不超过30个字',
            'cancelOnFail' => true
        )));
    }

    public function addUserWalletApi($data){
        $this->add('batch_no', new PresenceOf(array(
            'message' => 'batch_no is null',
            'cancelOnFail' => true
        )));
        $this->add('transactionId', new PresenceOf(array(
            'message' => 'transactionId is null',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => 'chain_symbol is null',
            'cancelOnFail' => true
        )));
        if($data['address'] != null) {
            $this->add('address', new PresenceOf(array(
                'message' => 'address is null',
                'cancelOnFail' => true
            )));
        }
        $this->add('status', new PresenceOf(array(
            'message' => 'status is null',
            'cancelOnFail' => true
        )));
    }

    //转出冷钱包
    public function transferColdWallet(){
        $this->add('password1', new PresenceOf(array(
            'message' => '请填写第一段密码',
            'cancelOnFail' => true
        )));
        $this->add('password1', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
            'message' => '第一段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
        $this->add('password2', new PresenceOf(array(
            'message' => '请填写第二段密码',
            'cancelOnFail' => true
        )));
        $this->add('password2', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
            'message' => '第二段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
        $this->add('password3', new PresenceOf(array(
            'message' => '请填写第三段密码',
            'cancelOnFail' => true
        )));
        $this->add('password3', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
            'message' => '第三段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
    }

    //EOS转出冷钱包
    public function transferEOSColdWallet(){
        $this->add('password1', new PresenceOf(array(
            'message' => '请填写第一段密码',
            'cancelOnFail' => true
        )));
        $this->add('password1', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
            'message' => '第一段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
        $this->add('password2', new PresenceOf(array(
            'message' => '请填写第二段密码',
            'cancelOnFail' => true
        )));
        $this->add('password2', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
            'message' => '第二段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
        $this->add('password3', new PresenceOf(array(
            'message' => '请填写第三段密码',
            'cancelOnFail' => true
        )));
        $this->add('password3', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
            'message' => '第三段密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
         $this->add('transferwallet_password', new PresenceOf(array(
             'message' => '请填写出账钱包密码',
             'cancelOnFail' => true
         )));
    }


    //转出冷钱包后的操作(接口)
    public function transferToPurse(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('transaction_no', new PresenceOf(array(
            'message' => 'transaction_no is null',
            'cancelOnFail' => true
        )));
        $this->add('batch_no', new PresenceOf(array(
            'message' => 'batch_no is null',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => 'type is null',
            'cancelOnFail' => true
        )));
        $this->add('coin_type', new PresenceOf(array(
            'message' => 'coin_type is null',
            'cancelOnFail' => true
        )));
        $this->add('hash', new PresenceOf(array(
            'message' => 'hash is null',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => 'address is null',
            'cancelOnFail' => true
        )));
        $this->add('from_address', new PresenceOf(array(
            'message' => 'from_address is null',
            'cancelOnFail' => true
        )));
        $this->add('leng', new PresenceOf(array(
            'message' => 'leng is null',
            'cancelOnFail' => true
        )));
        $this->add('num', new PresenceOf(array(
            'message' => 'num is null',
            'cancelOnFail' => true
        )));
        $this->add('fee', new PresenceOf(array(
            'message' => 'fee is null',
            'cancelOnFail' => true
        )));
        $this->add('status', new PresenceOf(array(
            'message' => 'status is null',
            'cancelOnFail' => true
        )));
    }

    public function finishTransferToPurse(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('transaction_no', new PresenceOf(array(
            'message' => 'transaction_no is null',
            'cancelOnFail' => true
        )));
    }

    public function addFeeNotice(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => 'chain_symbol is null',
            'cancelOnFail' => true
        )));
        $this->add('coin_symbol', new PresenceOf(array(
            'message' => 'coin_symbol is null',
            'cancelOnFail' => true
        )));
        $this->add('transaction_no', new PresenceOf(array(
            'message' => 'transaction_no is null',
            'cancelOnFail' => true
        )));
        $this->add('remark', new PresenceOf(array(
            'message' => 'remark is null',
            'cancelOnFail' => true
        )));
        $this->add('status', new PresenceOf(array(
            'message' => 'status is null',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => 'address is null',
            'cancelOnFail' => true
        )));
    }

    //提现拒绝
    public function withdrawrefuse(){
        $this->add('refuse_remark', new PresenceOf(array(
            'message' => '请填写拒绝理由',
            'cancelOnFail' => true
        )));
        $this->add('refuse_remark', new StringLength(array(
            'max' => 20,
            'messageMaximum' => '拒绝理由不超过20个字',
            'cancelOnFail' => true
        )));
    }

    //提现通过
    public function withdrawsuccess(){
        $this->add('password', new PresenceOf(array(
            'message' => '请输入出账钱包密码',
            'cancelOnFail' => true
        )));
//        $this->add('password', new Regex(array(
//            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
//            'message' => '密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
//            'cancelOnFail' => true
//        )));
    }

    public function dealmorewithdraw(){
        $this->add('ids', new PresenceOf(array(
            'message' => '请勾选订单',
            'cancelOnFail' => true
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请输入出账钱包密码',
            'cancelOnFail' => true
        )));
//        $this->add('password', new Regex(array(
//            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{4,16}$/',
//            'message' => '密码格式不正确,请填写包含英文及数字的4～16位字符密码', //密码格式错误
//            'cancelOnFail' => true
//        )));
    }

    //提现后的操作(接口)
    public function dealOrder(){
        $this->add('send_type', new PresenceOf(array(
            'message' => 'send_type is null',
            'cancelOnFail' => true
        )));
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => 'type is null',
            'cancelOnFail' => true
        )));
        $this->add('coin_type', new PresenceOf(array(
            'message' => 'coin_type is null',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => 'address is null',
            'cancelOnFail' => true
        )));
        $this->add('from_address', new PresenceOf(array(
            'message' => 'from_address is null',
            'cancelOnFail' => true
        )));
        $this->add('hash', new PresenceOf(array(
            'message' => 'hash is null',
            'cancelOnFail' => true
        )));
        $this->add('num', new PresenceOf(array(
            'message' => 'num is null',
            'cancelOnFail' => true
        )));
        $this->add('fee', new PresenceOf(array(
            'message' => 'fee is null',
            'cancelOnFail' => true
        )));
        $this->add('status', new PresenceOf(array(
            'message' => 'status is null',
            'cancelOnFail' => true
        )));
//        $this->add('remark', new PresenceOf(array(
//            'message' => 'remark is null',
//            'cancelOnFail' => true
//        )));
    }

    //记录手续费转出记录
    public function addFee(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => 'type is null',
            'cancelOnFail' => true
        )));
        $this->add('coin_type', new PresenceOf(array(
            'message' => 'coin_type is null',
            'cancelOnFail' => true
        )));
        $this->add('from_address', new PresenceOf(array(
            'message' => 'from_address is null',
            'cancelOnFail' => true
        )));
        $this->add('to_address', new PresenceOf(array(
            'message' => 'to_address is null',
            'cancelOnFail' => true
        )));
        $this->add('hash', new PresenceOf(array(
            'message' => 'hash is null',
            'cancelOnFail' => true
        )));
        $this->add('num', new PresenceOf(array(
            'message' => 'num is null',
            'cancelOnFail' => true
        )));
        $this->add('fee', new PresenceOf(array(
            'message' => 'fee is null',
            'cancelOnFail' => true
        )));
        $this->add('status', new PresenceOf(array(
            'message' => 'status is null',
            'cancelOnFail' => true
        )));

    }


    public function addproject(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('pro_name', new PresenceOf(array(
            'message' => 'pro_name is null',
            'cancelOnFail' => true
        )));
        $this->add('contacts_name', new PresenceOf(array(
            'message' => 'contacts_name is null',
            'cancelOnFail' => true
        )));
        $this->add('contacts_phone', new PresenceOf(array(
            'message' => 'contacts_phone is null',
            'cancelOnFail' => true
        )));
        $this->add('corporate_name', new PresenceOf(array(
            'message' => 'corporate_name is null',
            'cancelOnFail' => true
        )));
        $this->add('security_url', new PresenceOf(array(
            'message' => 'security_url is null',
            'cancelOnFail' => true
        )));
        $this->add('encryption_security_url', new PresenceOf(array(
            'message' => 'encryption_security_url is null',
            'cancelOnFail' => true
        )));
        $this->add('project_hash_url', new PresenceOf(array(
            'message' => 'project_hash_url is null',
            'cancelOnFail' => true
        )));
        $this->add('project_wallet_url', new PresenceOf(array(
            'message' => 'project_wallet_url is null',
            'cancelOnFail' => true
        )));
        $this->add('login_url', new PresenceOf(array(
            'message' => 'login_url is null',
            'cancelOnFail' => true
        )));
    }

    public function addchain(){
        $this->add('chain_name', new PresenceOf(array(
            'message' => 'chain_name is null',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => 'chain_symbol is null',
            'cancelOnFail' => true
        )));
        $this->add('chain_intro', new PresenceOf(array(
            'message' => 'chain_intro is null',
            'cancelOnFail' => true
        )));
        $this->add('publish_date', new PresenceOf(array(
            'message' => 'publish_date is null',
            'cancelOnFail' => true
        )));
        $this->add('issuance_total', new PresenceOf(array(
            'message' => 'issuance_total is null',
            'cancelOnFail' => true
        )));
        $this->add('circulate_total', new PresenceOf(array(
            'message' => 'circulate_total is null',
            'cancelOnFail' => true
        )));
        $this->add('initial_price', new PresenceOf(array(
            'message' => 'initial_price is null',
            'cancelOnFail' => true
        )));
        $this->add('white_paper', new PresenceOf(array(
            'message' => 'white_paper is null',
            'cancelOnFail' => true
        )));
        $this->add('website', new PresenceOf(array(
            'message' => 'website is null',
            'cancelOnFail' => true
        )));
        $this->add('blockchain', new PresenceOf(array(
            'message' => 'blockchain is null',
            'cancelOnFail' => true
        )));
//        $this->add('ip', new PresenceOf(array(
//            'message' => 'ip is null',
//            'cancelOnFail' => true
//        )));
    }

    //购买ram
    public function buyEOSRam(){
        $this->add('password', new PresenceOf(array(
            'message' => '请填写密码',
            'cancelOnFail' => true
        )));
        $this->add('ram_num', new PresenceOf(array(
            'message' => 'ram_num is null',
            'cancelOnFail' => true
        )));
        $this->add('ram_num', new Regex(array(
            'pattern' => '/^\d+(\.\d+)?$/',
            'message' => '购买数量填写不正确',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => 'chain_symbol is null',
            'cancelOnFail' => true
        )));
    }

    //购买Cpu Net
    public function buyEOSCpuNet(){
        $this->add('password', new PresenceOf(array(
            'message' => '请填写密码',
            'cancelOnFail' => true
        )));
        $this->add('cpu_num', new PresenceOf(array(
            'message' => 'cpu_num is null',
            'cancelOnFail' => true
        )));
        $this->add('cpu_num', new Regex(array(
            'pattern' => '/^\d+(\.\d+)?$/',
            'message' => '购买数量填写不正确',
            'cancelOnFail' => true
        )));
        $this->add('net_num', new PresenceOf(array(
            'message' => 'net_num is null',
            'cancelOnFail' => true
        )));
        $this->add('net_num', new Regex(array(
            'pattern' => '/^\d+(\.\d+)?$/',
            'message' => '购买数量填写不正确',
            'cancelOnFail' => true
        )));
        $this->add('chain_symbol', new PresenceOf(array(
            'message' => 'chain_symbol is null',
            'cancelOnFail' => true
        )));
    }

    //查询未添加币种订单
    public function selectOrder(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('token_contract', new PresenceOf(array(
            'message' => '请填写合约地址',
            'cancelOnFail' => true
        )));
        $this->add('abi', new PresenceOf(array(
            'message' => '请填写ABI',
            'cancelOnFail' => true
        )));
        $this->add('deposit_address', new PresenceOf(array(
            'message' => '请填写充值地址',
            'cancelOnFail' => true
        )));
        $this->add('hash', new PresenceOf(array(
            'message' => '请填写充值Hash',
            'cancelOnFail' => true
        )));
    }

    public function finishUnknownCoinTransfer(){
        $this->add('pro_no', new PresenceOf(array(
            'message' => 'pro_no is null',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => 'type is null',
            'cancelOnFail' => true
        )));
        $this->add('coin_type', new PresenceOf(array(
            'message' => 'coin_type is null',
            'cancelOnFail' => true
        )));
        $this->add('hash', new PresenceOf(array(
            'message' => 'hash is null',
            'cancelOnFail' => true
        )));
        $this->add('address', new PresenceOf(array(
            'message' => 'address is null',
            'cancelOnFail' => true
        )));
        $this->add('from_address', new PresenceOf(array(
            'message' => 'from_address is null',
            'cancelOnFail' => true
        )));
        $this->add('leng', new PresenceOf(array(
            'message' => 'leng is null',
            'cancelOnFail' => true
        )));
        $this->add('num', new PresenceOf(array(
            'message' => 'num is null',
            'cancelOnFail' => true
        )));
        $this->add('fee', new PresenceOf(array(
            'message' => 'fee is null',
            'cancelOnFail' => true
        )));
        $this->add('status', new PresenceOf(array(
            'message' => 'status is null',
            'cancelOnFail' => true
        )));
    }

}