<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

use App\Libs\SendSms;

class UnknownCoin extends BaseBussiness
{
    public function selectOrder($reqData){
        //判断项目是否存在
        $project = $this->getModel('Project')->getDetail($reqData['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //判断充值地址
        $userWallet = $this->getModel('ProjectUserWallet')->getByWhere($reqData['pro_no'],'address',$reqData['deposit_address']);
        if(!$userWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '充值地址不属于本系统';
            return $this->result;
        }

        //请求钱包查询订单
        $url = $this->config->wallet_ip.'api/getAccountMoneyByhash';
        $walletData = [
            'type'=>'ETH',//目前只做ETH系
            'token_contract'=>$reqData['token_contract'],
            'abi'=>$reqData['abi'],
            'deposit_address'=>$reqData['deposit_address'],
            'hash'=>$reqData['hash'],
        ];
        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
        if(!$reqWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '查询失败';
            return $this->result;
        }

        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '查询成功';
        $this->result['data']['deposit_address'] = $reqData['deposit_address'];
        $this->result['data']['hash'] = $reqData['hash'];
        $this->result['data']['batch_no'] = $userWallet[0]['batch_no'];
        $this->result['data']['password_prompt'] = $userWallet[0]['password_prompt'];
        $this->result['data']['amount'] = $reqWallet['data']['amount'];
        $this->result['data']['coin_type'] = $reqWallet['data']['coin_type'];
        $this->result['data']['decimals'] = $reqWallet['data']['decimals'];
//        $this->result['data']['amount'] = 1000;
//        $this->result['data']['coin_type'] = 'INC';
        return $this->result;
    }


    //转出冷钱包（项目流水增加其他币种）
    public function transfer($reqData){
        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($pro_no.':'.$adminSession['account'].':unknownCoinTransfer',10);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请求过快';
            return $this->result;
        }

        $project = $this->getModel('Project')->getDetail($pro_no,'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $admin = $this->getBussiness('RedisCache')->getLoginInfo($pro_no,$adminSession['account']);
        if(!$admin){
            $this->result['status'] = -1;
            $this->result['msg'] = '管理员信息不存在';
            return $this->result;
        }

        //判断批次是否存在
        $batchWallet = $this->getBussiness('ProjectWalletBatch')->getByWhere($pro_no,'batch_no',$reqData['batch_no']);
        if(!$batchWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '该批次记录不存在';
            return $this->result;
        }else{
            $batchWallet = $batchWallet[0];
        }


        //如果有正在进行中的转出历史，则不可再转出
        $transaction_list = $this->db->query("select count(id) as allcount from wallet_".$pro_no."_project_transaction where pro_no = '".$pro_no."' and batch_no = '".$reqData['batch_no']."' and title='转出冷钱包' and status = 0");
        $transaction_list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $transaction_list = $transaction_list->fetch();
        if($transaction_list['allcount'] > 0){
            $this->result['status'] = -1;
            $this->result['msg'] = '当前批次提交的转出冷钱包正在进行中';
            return $this->result;
        }


        //判断是否有冷钱包地址和手续费钱包地址,以及手续费钱包是否有钱
        $lengWallet = $this->getBussiness('ProjectWallet')->getByChainAndType($pro_no,$batchWallet['chain_id'],1);
        if(!$lengWallet){
            $this->result['status'] = -3;
            $this->result['msg'] = '请先设置冷钱包';
            return $this->result;
        }

        $feeWallet = $this->getBussiness('ProjectWallet')->getByChainAndType($pro_no,$batchWallet['chain_id'],3);
        if(!$feeWallet){
            $this->result['status'] = -3;
            $this->result['msg'] = '请先设置手续费钱包';
            return $this->result;
        }

        //判断手续费钱包余额
        $sql = "select sum(coin_balance) as allamount from wallet_".$pro_no."_project_wallets_assets where pro_no='".$pro_no."' and address='".$feeWallet['address']."'";
        $assetAmount = $this->db->query($sql);
        $assetAmount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $assetAmount = $assetAmount->fetch();
        if($assetAmount['allamount'] == 0){
            $this->result['status'] = -1;
            $this->result['msg'] = '手续费钱包余额为0';
            return $this->result;
        }


        //密码
        $reqData['password'] = $reqData['password1'].$reqData['password2'].$reqData['password3'];

        //查询错误次数
        $key = $pro_no.":".$adminSession['admin_no'].':unknowncointransfer:passwordError';
        $errorAccount = $this->getBussiness('RedisCache')->setError($key);
        if(!$errorAccount){
            $redisErrorAccount=0;
            $this->getBussiness('RedisCache')->editError($key,$redisErrorAccount,1800);
            $errorAccount = $redisErrorAccount;
        }

        //判断密码正确
        if($batchWallet['password'] != md5($reqData['password'])){
            //redis错误次数+1
            $redisErrorAccount = $errorAccount+1;
            $this->getBussiness('RedisCache')->editError($key,$redisErrorAccount,1800);

            if($errorAccount >= 4){
                //将登陆的redis改为错误5次
                $this->getBussiness('RedisCache')->setLoginInfo($pro_no,$adminSession['account'],['errorAccount'=>5],1800);

                //发短信给超级管理员
                $superAdmin = $this->getModel('Admin')->getSuper($pro_no,$filed='phone');
                if($superAdmin){
                    //发短信
                    $sms['templateId'] = '21244';
                    $sms['phone'] = $superAdmin['phone'];
                    $sms['vars'] = '{"%adminname%":"'.$adminSession['account'].'","%content%":"转出未添加币种"}';
                    $SendSms = new SendSms();
                    $SendSms->send_sms($sms);
                }

                //退出登陆
                $this->result['status'] = -2;
                $this->result['msg'] = '密码错误五次,请三十分钟后再试';
                return $this->result;

            }

            $this->result['status'] = -1;
            $this->result['msg'] = '密码错误';
            return $this->result;
        }

        //请求钱包转出
        $url = $this->config->wallet_ip . 'api/financeWithdrawlByhash';
        //请求钱包数据
        $walletData = [
            'pro_no' => $pro_no,
            'type' => 'ETH',
            'coin_type' => $reqData['coin_type'],
            'address' => $reqData['address'],
            'abi' => $reqData['abi'],
            'contract_address' => $reqData['token_contract'],
            'fee_wallet_address' => $feeWallet['address'],
            'fee_wallet_password' => $feeWallet['password'],
            'leng' => $lengWallet['address'],
            'num' => $reqData['num'],
            'password' => $reqData['password'],
            'decimals' => $reqData['decimals'],
        ];

//        var_dump($walletData);
        $reqWallet = $this->functions->http_request_forWallet($url, 'POST', $walletData);
//        var_dump($reqWallet);

        if (!isset($reqWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '转出请求失败';
            return $this->result;
        }

        if($reqWallet['status'] == -1){
            $this->result['status'] = -1;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }elseif($reqWallet['status'] == 2){
            //手续费转出成功
            $this->result['status'] = 2;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }

        //记录日志
        $sqlData['pro_no'] = $pro_no;
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_transaction';
        $sqlData['sql'] = '';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'insert';
        $sqlData['log_title'] = '转出未添加币种('.$reqData['address'].')';

        $this->getModel('AdminLog')->add($sqlData);


        $this->result['status'] = 1;
        $this->result['msg'] = $reqWallet['msg'];
        return $this->result;


    }
    
    //钱包通知 转出完成 扣除手续费资金 记录项目流水
    public function finishTransfer($reqData){
        //判断项目是否存在
        $project = $this->getModel('Project')->getDetail($reqData['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$reqData['type']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }

        //更新公链手续费--手续费字段及公链余额
        $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`-".$reqData['fee'].",updated_at=".time()." WHERE address='".$reqData['address']."' and chain_symbol='".$chain['chain_symbol']."' and pro_no='".$reqData['pro_no']."'";
        $this->db->query($sqlAsset);
        $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$reqData['fee'].",updated_at=".time()." WHERE address='".$reqData['address']."' and coin_symbol='".$chain['chain_symbol']."' and pro_no='".$reqData['pro_no']."'";
        $this->db->query($sqlAsset);

        //转到冷钱包记录项目流水
        $flowData = [
            'pro_no'=>$reqData['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$reqData['from_address'],
            'to_address'=>$reqData['leng'],
            'chain_id'=>$chain['id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'hash'=>$reqData['hash'],
            'coin_chain_amount'=>$reqData['fee'],
            'coin_id'=>0,//其他
            'coin_symbol'=>$reqData['coin_type'],
            'coin_amount'=>$reqData['num'],
            'flow_type'=>4,//1出账钱包充值，2 手续费钱包充值，3：手续费钱包转出4：转入冷钱包
            'created_at'=>time(),
            'updated_at'=>time(),
            'status'=>($reqData['status'] == 1)?1:0,
        ];

        $this->getModel('ProjectServerWalletsFlow')->add($flowData);

        $this->result['status'] = 1;
        $this->result['msg'] = '通知成功';
        return $this->result;
    }
}