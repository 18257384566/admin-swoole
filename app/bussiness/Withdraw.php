<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

use App\Libs\SendSms;

class Withdraw extends BaseBussiness
{
    public function getById($id){
        return $this->getModel('Withdraw')->getById($id,$filed = '*');
    }

    public function updateById($id,$data){
        return $this->getModel('Withdraw')->updateById($id,$data);
    }

    public function refuse($id,$data){
        $adminSession = $this->session->get('backend');
        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':withdraw:'.$id,10);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单不能重复操作';
            return $this->result;
        }

        $order = $this->getById($id);
        if(!$order){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单不存在';
            return $this->result;
        }

        if($order['status'] != 0){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单已处理，请勿重复操作';
            return $this->result;
        }



        $this->view->adminName = $adminSession['account'];
        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }


        //获取出账钱包
        $wallet = $this->getModel('ProjectWallet')->getByChainAndType($adminSession['pro_no'],$order['chain_id'],$type=2,$filed='*');
        if(!$wallet){
            $this->result['status'] = -3;
            $this->result['msg'] = '请先设置出账钱包';
            return $this->result;
        }

        $data = [
            'status'=>3,
            'refuse_remark'=>$data['refuse_remark'],
            'updated_at'=>time(),
            'admin_name'=>$adminSession['account'],
            'admin_no'=>$adminSession['admin_no'],
        ];
        $update = $this->getModel('Withdraw')->updateById($id,$data);
        if(!$update){
            $this->result['status'] = -1;
            $this->result['msg'] = '处理失败';
            return $this->result;
        }

        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $adminSession['pro_name'];
        $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_withdraw';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'update';
        $sql['log_title'] = '拒绝了id为'.$id.'的提现订单';
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);


        //通知项目方
        $noticeData = [
            'send_type'=>'withdraw',
            'status'=>3,
            'from_address'=>$wallet['address'],
            'to_address'=>$order['address'],
            'num'=>(string)$order['coin_amount'],
            'hash'=>'',
            'fee'=>'0',
            'coin_type'=>$order['coin_symbol'],
            'pro_no'=>$adminSession['pro_no'],
            'order_no'=>$order['withdraw_no'],
        ];


        $reqWallet = $this->getBussiness('Api')->sendHashChange($noticeData);
        if (!$reqWallet) {
//            通知失败
//            记录
        }


        $this->result['status'] = 1;
        $this->result['msg'] = '处理成功';
        $this->result['coin_id'] = $order['coin_id'];
        return $this->result;

    }


    public function success($id,$reqData){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':withdraw:'.$id,10);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请勿重复操作';
            return $this->result;
        }



        $order = $this->getById($id);
        if(!$order){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单不存在';
            return $this->result;
        }

        if($order['status'] != 0 && $order['status'] != 4){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单已处理，请勿重复操作';
            return $this->result;
        }

        $pro_no = $adminSession['pro_no'];
        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'pro_name,pro_no');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $coin = $this->getModel('ProjectCoin')->getById($order['coin_id']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种信息不存在';
            return $this->result;
        }


//        $userWallet = $this->getBussiness('ProjectUserWallet')->getByWhere($pro_no,'address',$order['address']);
//        if(!$userWallet){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '地址不存在';
//            return $this->result;
//        }

        //获取出账钱包
        $wallet = $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$order['chain_id'],$type=2,$filed='*');
        if(!$wallet){
            $this->result['status'] = -3;
            $this->result['msg'] = '请先设置出账钱包';
            return $this->result;
        }

        //不可以提现到出账钱包
        if($order['address'] == $wallet['address']){
            $this->result['status'] = -1;
            $this->result['msg'] = '钱包地址无效';
            return $this->result;
        }

        //EOS
//        if($order['chain_symbol'] == 'EOS' && $order['address'] == $this->config->from_address){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '钱包地址无效';
//            return $this->result;
//        }



        //查询错误次数
        $key = $pro_no.":".$adminSession['admin_no'].':withdraw:passwordError';
        $errorAccount = $this->getBussiness('RedisCache')->setError($key);
        if(!$errorAccount){
            $redisErrorAccount=0;
            $this->getBussiness('RedisCache')->editError($key,$redisErrorAccount,1800);
            $errorAccount = $redisErrorAccount;
        }

        //判断出账钱包密码
        if($wallet['password'] != md5($reqData['password'])){
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
                    $sms['vars'] = '{"%adminname%":"'.$adminSession['account'].'","%content%":"提币"}';
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


        //获取出账钱包资产记录
        $transferWalletAsset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($wallet['address'],$order['coin_symbol']);
        if(!$transferWalletAsset){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包暂无资金';
            return $this->result;
        }

        //判断出账钱包资产
        if($transferWalletAsset['coin_balance'] < ($order['coin_amount']+$this->config->config_amount[$coin['chain_symbol']])){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包中的余额或手续费不足';
            return $this->result;
        }

        //更新出账钱包余额
        if($coin['chain_symbol'] == 'EOS'){
            $update['coin_balance_update'] = $transferWalletAsset['coin_balance'];
            $update['chain_fee_balance_update'] = $transferWalletAsset['chain_fee_balance'];
            $update['updated_at'] = time();
        }else{
            $url = $this->config->wallet_ip.'api/getAccountMoney';
            $walletData['coin_type'] = $coin['coin_symbol'];
            $walletData['type'] = $coin['chain_symbol'];
            $walletData['address'] = $wallet['address'];
            $walletData['abi'] = $coin['coin_abi'];
            $walletData['contract_address'] = $coin['token_contract'];

            $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
            if (!isset($reqWallet)) {
                $this->result['status'] = -1;
                $this->result['msg'] = '钱包确认金额接口请求失败';
                return $this->result;
            }

            if($reqWallet['status'] != 1){
                $this->result['status'] = -1;
                $this->result['msg'] = $reqWallet['msg'];
                return $this->result;
            }

            $update['coin_balance_update'] = $reqWallet['data'][$coin['coin_symbol']]['data'];
            $update['chain_fee_balance_update'] = $reqWallet['data'][$coin['chain_symbol']]['data'];
            $update['updated_at'] = time();
        }

        $this->getModel('ProjectWalletAsset')->updateById($transferWalletAsset['id'],$update);



        //判断出账钱包资产（链上）
        if($update['coin_balance_update'] < ($order['coin_amount']+$this->config->config_amount[$coin['chain_symbol']])){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包中的余额或手续费不足';
            return $this->result;
        }

        $configAmount = $this->config->config_amount[$coin['chain_symbol']];
        if($update['chain_fee_balance_update'] < $configAmount){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包手续费低于'.$configAmount;
            return $this->result;
        }

        //判断手续费钱包
//        $feewallet = $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$order['chain_id'],$type=3,$filed='*');
//        if(!$feewallet){
//            $this->result['status'] = -3;
//            $this->result['msg'] = '请先设置手续费钱包';
//            return $this->result;
//        }

        //判断手续费钱包资产
//        $feewalletAsset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($feewallet['address'],$order['coin_id']);
//        if(!$feewalletAsset || $feewalletAsset['coin_balance'] <= 0){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '手续费钱包余额不足';
//            return $this->result;
//        }





        //请求钱包
        $url = $this->config->wallet_ip.'api/withDrawl';
        $noticeData = [
            'pro_no'=>$pro_no,
            'type'=>$order['chain_symbol'],
            'coin_type'=>$order['coin_symbol'],
            'to_address'=>$order['address'],
            'password'=>$reqData['password'],
            'contract_address'=>$coin['token_contract'],
            'abi'=>$coin['coin_abi'],
            'memo'=>$order['memo'],
            'from_address'=>$wallet['address'],
        ];
        //EOS
        if($order['chain_symbol'] == 'EOS'){
            $noticeData['num'] = $order['coin_amount'] = sprintf("%.4f",$order['coin_amount']);
        }else{
            $noticeData['num'] = $order['coin_amount'];
        }

        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$noticeData);
        if(!$reqWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '钱包接口请求失败';
            return $this->result;
        }

        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }

        //扣出账钱包资产
        $sqlAsset = "UPDATE wallet_".$pro_no."_project_wallets_assets SET coin_balance = `coin_balance`-".$order['coin_amount'].",updated_at=".time()." WHERE address='".$wallet['address']."' and coin_id='".$order['coin_id']."' and pro_no='".$pro_no."'";
        $this->db->query($sqlAsset);

        if($order['coin_symbol'] == $order['chain_symbol']){
            $sqlAsset = "UPDATE wallet_".$pro_no."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`-".$order['coin_amount'].",updated_at=".time()." WHERE address='".$wallet['address']."' and chain_symbol='".$order['chain_symbol']."' and pro_no='".$pro_no."'";
            $this->db->query($sqlAsset);
        }

        //记录流水
        $flowData = [
            'pro_no'=>$project['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$wallet['address'],
            'to_address'=>$order['address'],
            'chain_id'=>$order['chain_id'],
            'chain_symbol'=>$order['chain_symbol'],
            'hash'=>$reqWallet['data'],
            'coin_chain_amount'=>0,//暂定，钱包成功后返回
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$order['coin_amount'],
            'obj_name'=>'wallet_'.$adminSession['pro_no'].'_project_withdraw',//表名
            'obj_id'=>$id,
            'flow_type'=>0,//出账
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('ProjectWalletsFlow')->add($flowData);

        //修改订单状态
        $data = [
            'status'=>1,
            'updated_at'=>time(),
            'admin_name'=>$adminSession['account'],
            'admin_no'=>$adminSession['admin_no'],
        ];
        $update = $this->getModel('Withdraw')->updateById($id,$data);

        if(!$update){
            $this->result['status'] = -1;
            $this->result['msg'] = '处理失败';
            return $this->result;
        }


        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'update';
        $sqlData['log_title'] = '处理了id为'.$id.'的提现订单通过';
        $sqlData['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sqlData);


        $this->result['status'] = 1;
        $this->result['msg'] = '提现操作成功';
        $this->result['coin_id'] = $coin['id'];
        return $this->result;

    }


    //批量提现
    public function dealMoreOrders($reqData){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':withdraw:more:success',10);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请勿重复操作';
            return $this->result;
        }


        $pro_no = $adminSession['pro_no'];
        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'pro_name,pro_no');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $coin = $this->getModel('ProjectCoin')->getById($reqData['coin_id']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种信息不存在';
            return $this->result;
        }

        //获取出账钱包
        $wallet = $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$coin['chain_id'],$type=2,$filed='*');
        if(!$wallet){
            $this->result['status'] = -3;
            $this->result['msg'] = '请先设置出账钱包';
            return $this->result;
        }



        //查询错误次数
        $key = $pro_no.":".$adminSession['admin_no'].':withdraw:more:passwordError';
        $errorAccount = $this->getBussiness('RedisCache')->setError($key);
        if(!$errorAccount){
            $redisErrorAccount=0;
            $this->getBussiness('RedisCache')->editError($key,$redisErrorAccount,1800);
            $errorAccount = $redisErrorAccount;
        }
        //判断密码
        if($wallet['password'] != md5($reqData['password'])){
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
                    $sms['vars'] = '{"%adminname%":"'.$adminSession['account'].'","%content%":"提币"}';
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


        //获取出账钱包资产
        $transferWalletAsset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($wallet['address'],$coin['coin_symbol']);
        if(!$transferWalletAsset){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包暂无资金';
            return $this->result;
        }


        //计算批量的金额
        $ids = $reqData['ids'];
        $allamount = $this->db->query("select sum(coin_amount) as allamount from wallet_".$pro_no."_project_withdraw where id in($ids)");
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();


        $idsArr = explode(',',$ids);//批量订单的id

        //判断出账钱包资产
        if($transferWalletAsset['coin_balance'] < ($allamount['allamount']+count($idsArr)*$this->config->config_amount[$coin['chain_symbol']])){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包中的余额或手续费不足';
            return $this->result;
        }

        //更新出账钱包余额
        if($coin['chain_symbol'] == 'EOS'){
            $update['coin_balance_update'] = $transferWalletAsset['coin_balance'];
            $update['chain_fee_balance_update'] = $transferWalletAsset['chain_fee_balance'];
            $update['updated_at'] = time();
        }else{
            $url = $this->config->wallet_ip.'api/getAccountMoney';
            $walletData['coin_type'] = $coin['coin_symbol'];
            $walletData['type'] = $coin['chain_symbol'];
            $walletData['address'] = $wallet['address'];
            $walletData['abi'] = $coin['coin_abi'];
            $walletData['contract_address'] = $coin['token_contract'];

            $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
            if (!isset($reqWallet)) {
                $this->result['status'] = -1;
                $this->result['msg'] = '钱包确认金额接口请求失败';
                return $this->result;
            }

            if($reqWallet['status'] != 1){
                $this->result['status'] = -1;
                $this->result['msg'] = $reqWallet['msg'];
                return $this->result;
            }

            $update['coin_balance_update'] = $reqWallet['data'][$coin['coin_symbol']]['data'];
            $update['chain_fee_balance_update'] = $reqWallet['data'][$coin['chain_symbol']]['data'];
            $update['updated_at'] = time();
        }

        $this->getModel('ProjectWalletAsset')->updateById($transferWalletAsset['id'],$update);



        //判断出账钱包资产
        if($update['coin_balance_update'] < ($allamount['allamount']+count($idsArr)*$this->config->config_amount[$coin['chain_symbol']])){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包中的余额或手续费不足';
            return $this->result;
        }

        $configAmount = $this->config->config_amount[$coin['chain_symbol']];
        if($update['chain_fee_balance_update'] < $configAmount){
            $this->result['status'] = -1;
            $this->result['msg'] = '出账钱包手续费低于'.$configAmount;
            return $this->result;
        }

        //判断手续费钱包
//        $feewallet = $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$coin['chain_id'],$type=3,$filed='*');
//        if(!$feewallet){
//            $this->result['status'] = -3;
//            $this->result['msg'] = '请先设置手续费钱包';
//            return $this->result;
//        }

        //判断手续费钱包资产
//        $feewalletAsset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($feewallet['address'],$reqData['coin_id']);
//        if(!$feewalletAsset || $feewalletAsset['coin_balance'] <= 0){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '手续费钱包余额不足';
//            return $this->result;
//        }




        //新增事务
        $transactionData = [
            'transaction_no' =>date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).rand(1000,9999),
            'batch_no' => '',
            'title' => '批量提现',
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_id'=>$coin['id'],
            'chain_id'=>$coin['chain_id'],
            'chain_symbol'=>$coin['chain_symbol'],
            'count' => count($idsArr),
            'success'=>0,
            'fail'=>0,
            'incomplete' => count($idsArr),
            'amount'=>$allamount['allamount'],
            'pro_no' => $adminSession['pro_no'],
            'pro_name' => $project['pro_name'],
            'admin_name' => $adminSession['account'],
            'admin_no' => $adminSession['admin_no'],
            'created_at' => time(),
            'updated_at' =>time(),
        ];
        $this->getBussiness('ProjectTransaction')->add($transactionData);


        foreach ($idsArr as $id){
            //判断订单是否存在
            $order = $this->getById($id);
            if(!$order){
                //更新事务表 错误次数加1
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $transactionData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
                $this->db->query($sql);
                continue;
            }

            if($order['status'] != 0){
                //更新事务表 错误次数加1
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $transactionData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
                $this->db->query($sql);
                continue;
            }

            //不可以提现到出账钱包
            if($order['address'] == $wallet['address']){
                //更新事务表 错误次数加1
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $transactionData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
                $this->db->query($sql);
                continue;
            }

            //EOS
//            if($order['chain_symbol'] == 'EOS' && $order['address'] == $this->config->from_address){
//                //更新事务表 错误次数加1
//                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $transactionData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
//                $this->db->query($sql);
//                continue;
//            }


            //请求钱包
            $url = $this->config->wallet_ip.'api/withDrawl';
            $noticeData = [
                'pro_no'=>$pro_no,
                'type'=>$order['chain_symbol'],
                'coin_type'=>$order['coin_symbol'],
                'to_address'=>$order['address'],
                'password'=>$reqData['password'],
                'contract_address'=>$coin['token_contract'],
                'abi'=>$coin['coin_abi'],
                'memo'=>$order['memo'],
                'from_address'=>$wallet['address'],
            ];

            //EOS
            if($order['chain_symbol'] == 'EOS'){
                $noticeData['num'] = $order['coin_amount'] = sprintf("%.4f",$order['coin_amount']);
            }else{
                $noticeData['num'] = $order['coin_amount'];
            }

            $reqWallet = $this->functions->http_request_forWallet($url,'POST',$noticeData);
            if(!$reqWallet){
                //更新事务表 错误次数加1
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $transactionData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
                $this->db->query($sql);
//                $this->functions->alert('钱包接口请求失败');
                continue;
            }

            if($reqWallet['status'] != 1){
                //更新事务表 错误次数加1
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $transactionData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
                $this->db->query($sql);
//                $this->functions->alert($reqWallet['msg']);
                continue;
            }

            //扣出账钱包资产
            $sqlAsset = "UPDATE wallet_".$pro_no."_project_wallets_assets SET coin_balance = `coin_balance`-".$order['coin_amount'].",updated_at=".time()." WHERE address='".$wallet['address']."' and coin_id='".$order['coin_id']."' and pro_no='".$pro_no."'";
            $this->db->query($sqlAsset);

            if($order['coin_symbol'] == $order['chain_symbol']){
                $sqlAsset = "UPDATE wallet_".$pro_no."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`-".$order['coin_amount'].",updated_at=".time()." WHERE address='".$wallet['address']."' and chain_symbol='".$order['chain_symbol']."' and pro_no='".$pro_no."'";
                $this->db->query($sqlAsset);
            }

            //记录流水
            $flowData = [
                'pro_no'=>$project['pro_no'],
                'pro_name'=>$project['pro_name'],
                'from_address'=>$wallet['address'],
                'to_address'=>$order['address'],
                'chain_id'=>$order['chain_id'],
                'chain_symbol'=>$order['chain_symbol'],
                'hash'=>$reqWallet['data'],
                'coin_chain_amount'=>0,//暂定，钱包成功后返回
                'coin_id'=>$coin['id'],
                'coin_symbol'=>$coin['coin_symbol'],
                'coin_amount'=>$order['coin_amount'],
                'obj_name'=>'wallet_'.$project['pro_no'].'_project_withdraw',//表名
                'obj_id'=>$id,
                'flow_type'=>0,//出账
                'created_at'=>time(),
                'updated_at'=>time(),
            ];
            $this->getModel('ProjectWalletsFlow')->add($flowData);

            //修改订单状态
            $data = [
                'status'=>1,
                'updated_at'=>time(),
                'admin_name'=>$adminSession['account'],
                'admin_no'=>$adminSession['admin_no'],
            ];
            $this->getModel('Withdraw')->updateById($id,$data);


        }


        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'update';
        $sqlData['log_title'] = '批量提币，订单id为'.$ids;
        $sqlData['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sqlData);

        $this->result['status'] = 1;
        $this->result['msg'] = '成功';
        $this->result['coin_id'] = $coin['id'];
        return $this->result;
    }




    //更新提现订单api
    public function dealOrder($data){
        //获取项目信息
        $project = $this->getModel('Project')->getDetail($data['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //查询订单流水
        $flow=$this->db->query("select id,obj_id from wallet_".$data['pro_no']."_project_wallets_flow where pro_no = '".$data['pro_no']."' and obj_name = 'wallet_".$data['pro_no']."_project_withdraw' and hash='".$data['hash']."'  and flow_type=0 and coin_symbol='".$data['coin_type']."' and to_address='".$data['to_address']."'");
        $flow->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $flow = $flow->fetch();
        if(!$flow){
            $this->result['status'] = -1;
            $this->result['msg'] = 'hash不存在';
            return $this->result;
        }

        $order = $this->getById($flow['obj_id']);
        if(!$order){
            $this->result['status'] = -1;
            $this->result['msg'] = '订单不存在';
            return $this->result;
        }


        //修改订单和流水
        if($data['status'] == 1){
            //成功
            $updatedata = [
                'status'=>2,
                'updated_at'=>time(),
            ];
            $this->updateById($order['id'],$updatedata);

            $flowData = [
                'status'=>1,
                'coin_chain_amount'=>$data['fee'],
            ];
            $this->getModel('ProjectWalletsFlow')->updateById($flow['id'],$flowData);
//            $noticeData['status'] = 1;

        }else{
            //失败
            $updatedata = [
                'status'=>4,
                'remark'=>$data['remark'],
                'updated_at'=>time(),
            ];
            $this->updateById($order['id'],$updatedata);

            //返回账钱包资产
            $sqlAsset = "UPDATE wallet_".$data['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`+".$data['num'].",updated_at=".time()." WHERE address='".$data['from_address']."' and coin_id='".$order['coin_id']."' and pro_no='".$order['pro_no']."'";
            $this->db->query($sqlAsset);

            if($order['coin_symbol'] == $order['chain_symbol']){
                $sqlAsset = "UPDATE wallet_".$data['pro_no']."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`+".$data['num'].",updated_at=".time()." WHERE address='".$data['from_address']."' and chain_symbol='".$order['chain_symbol']."' and pro_no='".$order['pro_no']."'";
                $this->db->query($sqlAsset);
            }

            $flowData = [
                'status'=>0,
                'coin_chain_amount'=>$data['fee'],
            ];
            $this->getModel('ProjectWalletsFlow')->updateById($flow['id'],$flowData);
//            $noticeData['status'] = 2;
        }



        $sqlAssetBalance = "UPDATE wallet_".$data['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$data['fee'].",updated_at=".time()." WHERE address='".$data['from_address']."' and coin_symbol='".$order['chain_symbol']."' and pro_no='".$order['pro_no']."' ";
//        var_dump($sqlAsset);
        $this->db->query($sqlAssetBalance);


        $sqlAssetChainBalance = "UPDATE wallet_".$data['pro_no']."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`-".$data['fee'].",updated_at=".time()." WHERE address='".$data['from_address']."' and chain_symbol='".$order['chain_symbol']."' and pro_no='".$order['pro_no']."'";
//        dd($sqlAsset);
        $this->db->query($sqlAssetChainBalance);

        if($data['status'] == 1) {
            //提现成功通知项目方
            $noticeData = [
                'send_type' => 'withdraw',
                'status' => $data['status'],
                'from_address' => $data['from_address'],
                'to_address' => $data['to_address'],
                'num' => $data['num'],
                'hash' => $data['hash'],
                'fee' => $data['fee'],
                'coin_type' => $data['coin_type'],
                'pro_no' => $data['pro_no'],
                'order_no' => $order['withdraw_no'],
            ];


            $reqWallet = $this->getBussiness('Api')->sendHashChange($noticeData);
            if (!$reqWallet) {
                //通知失败
                //记录
            }
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '成功';
        return $this->result;


    }

}