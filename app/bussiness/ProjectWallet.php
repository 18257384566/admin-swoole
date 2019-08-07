<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

use App\Libs\SendSms;

class ProjectWallet extends BaseBussiness
{
    public function getList($pro_no,$type){
        return $this->getModel('ProjectWallet')->getList($pro_no,$type,$field='*');
    }

    public function getById($id){
        return $this->getModel('ProjectWallet')->getById($id,$field='*');
    }

    public function getByChainAndType($pro_no,$chain_id,$type){
        return $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$chain_id,$type,$filed='*');
    }


    public function apiForAddWallet($reqData,$type=0){
        if($type == 1){
            //冷钱包
            $url = $this->config->wallet_ip.'api/validationAddress';
            $walletData['address'] = $reqData['address'];

        }else{
            //手续费钱包地址的密码存真实的
            $url = $this->config->wallet_ip.'api/getAccount';
            $walletData['password'] = $reqData['password'];
            if(isset($reqData['address_type'])){
                $walletData['address_type'] = $reqData['address_type'];
            }
        }

        //拼接参数公链名
        $walletData['type'] = $reqData['type'];
        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
        if (!isset($reqWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '钱包接口请求失败';
            return $this->result;
        }
        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }

        if($type != 1){
            //冷钱包不需要存redis
            $this->getBussiness('RedisCache')->addWalletAddress($reqWallet['data']);

            //存wallet_type pro_no
            if($type == 2){
                $redisData = [
                    'pro_no'=>$reqData['pro_no'],
                    'wallet_type'=>'transaction',
                ];
            }elseif ($type == 3){
                $redisData = [
                    'pro_no'=>$reqData['pro_no'],
                    'wallet_type'=>'fee',
                ];
            }elseif ($type == 0){
                $redisData = [
                    'pro_no'=>$reqData['pro_no'],
                    'wallet_type'=>'user',
                ];
            }

            $this->getBussiness('RedisCache')->addWalletAddressType($reqWallet['data'],$redisData);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '';
        $this->result['data'] = $reqWallet['data'];
        return $this->result;

    }

    public function add($type,$reqData){
        $adminSession = $this->session->get('backend');

        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].'addProjectWallet',10);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请求过快';
            return $this->result;
        }

        //---------EOS
        if($reqData['chain_symbol'] == 'EOS' && $type == 3){
            $this->result['status'] = -1;
            $this->result['msg'] = 'EOS公链无法生成手续费钱包';
            return $this->result;
        }

        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $chain = $this->getModel('SystemChain')->getById($reqData['chain_id']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }


        //判断地址是否存在
        $wallet = $this->getByChainAndType($adminSession['pro_no'],$chain['id'],$type);
        if($wallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '钱包记录已存在';
            return $this->result;
        }

        //冷钱包地址不能和出账钱包地址是同一个
        //查询出账钱包地址
        $transactionWallet = $this->getModel('ProjectWallet')->getByChainAndType($adminSession['pro_no'],$reqData['chain_id'],2,$filed='*');
        if(isset($transactionWallet)){
            if($type == 1 && $reqData['address'] == $transactionWallet['address']){
                $this->result['status'] = -1;
                $this->result['msg'] = '地址不合法';
                return $this->result;
            }
        }


        //---------EOS生成出账钱包
        if($reqData['chain_symbol'] == 'EOS' && $type == 2){
            //请求钱包获取密码
            $url = $this->config->wallet_ip.'api/createWallet';
            $walletData['type'] = $reqData['chain_symbol'];
            $walletData['account_name'] = $reqData['address'];
            $apiForAddWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
            if (!isset($apiForAddWallet)) {
                $this->result['status'] = -1;
                $this->result['msg'] = '钱包接口请求失败';
                return $this->result;
            }
            if($apiForAddWallet['status'] != 1){
                if($apiForAddWallet['status'] == -2){
                    //主钱包余额不足时需要发短信给新贝运营
                    $sms['templateId'] = '27690';
                    $sms['phone'] = $this->config->system_worker_phone;
                    $sms['vars'] = '{"%project%":"'.$project['pro_name'].'","%notice%":"余额"}';
                    $SendSms = new SendSms();
                    $SendSms->send_sms($sms);
                }elseif($apiForAddWallet['status'] == -3){
                    $sms['templateId'] = '27690';
                    $sms['phone'] = $this->config->system_worker_phone;
                    $sms['vars'] = '{"%project%":"'.$project['pro_name'].'","%notice%":"资源"}';
                    $SendSms = new SendSms();
                    $SendSms->send_sms($sms);
                }

                $this->result['status'] = -1;
                $this->result['msg'] = $apiForAddWallet['msg'];
                return $this->result;

            }

            //存redis
            $this->redis->sAdd('wallet_center:set:eos:address',$reqData['address']);

            $redisData = [
                'pro_no'=>$adminSession['pro_no'],
                'wallet_type'=>'transaction',
            ];
            $this->getBussiness('RedisCache')->addWalletAddressType('EOS_address_'.$reqData['address'],$redisData);


            //把密码发短信给超管
            $superAdmin = $this->getModel('Admin')->getSuper($adminSession['pro_no'],$filed='phone');
            if($superAdmin){
                //发短信
                $sms['templateId'] = '26691';
                $sms['phone'] = $superAdmin['phone'];
                //模板 26691 %password%
                $sms['vars'] = '{"%password%":"'.$apiForAddWallet['data'].'"}';
                $SendSms = new SendSms();
                $SendSms->send_sms($sms);
            }

        }else{
            $reqData['pro_no'] = $adminSession['pro_no'];
            $reqData['type'] = $chain['chain_symbol'];
            //验证地址合法性
            if(in_array($chain['chain_symbol'],$this->config->address_type_chain->toArray()) != false){
                $reqData['address_type'] = $adminSession['pro_no'].'admin';
            }

            $apiForAddWallet = $this->apiForAddWallet($reqData,$type);
            if($apiForAddWallet['status'] != 1){
                $this->result['status'] = -1;
                $this->result['msg'] = $apiForAddWallet['msg'];
                return $this->result;
            }
        }


        $add['pro_no'] = $adminSession['pro_no'];
        $add['pro_name'] = $project['pro_name'];
        $add['wallet_type'] = $type;
        if($type == 1){
            //添加冷钱包
            $add['address'] = $reqData['address'];
            $add['memo'] = $reqData['memo'];
        }elseif($type == 3){
            //手续费钱包（数据库存真实的）
            $add['address'] = $apiForAddWallet['data'];
            $add['password'] = $reqData['password'];
        }elseif($type == 2 && $reqData['chain_symbol'] == 'EOS'){
            //添加出账钱包
            $add['address'] = $reqData['address'];
            $add['password'] = md5($apiForAddWallet['data']);
        }elseif($type == 2 && $reqData['chain_symbol'] != 'EOS'){
            //添加出账钱包
            $add['address'] = $apiForAddWallet['data'];
            $add['password'] = md5($reqData['password']);
        }

        $add['chain_symbol'] = $chain['chain_symbol'];
        $add['chain_id'] = $reqData['chain_id'];
        $add['admin_name'] = $adminSession['account'];
        $add['admin_no'] = $adminSession['admin_no'];
        $add['created_at'] = $add['updated_at'] = time();

        $id = $this->getModel('ProjectWallet')->add($add);

        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $adminSession['pro_name'];
        $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_wallets';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'insert';
        if($type == 1){
            $sql['log_title'] = $adminSession['account'].'添加了'.$add['chain_symbol'].'的冷钱包地址';
        }elseif($type == 2){
            $sql['log_title'] = $adminSession['account'].'添加了'.$add['chain_symbol'].'的出账钱包地址';
        }elseif($type == 3){
            $sql['log_title'] = $adminSession['account'].'添加了'.$add['chain_symbol'].'的手续费钱包地址';
        }
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);

        //生成公链代币
        $projectcoin = $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'],'coin_symbol',$chain['chain_symbol']);
        if(!$projectcoin){
            $add['pro_no'] = $adminSession['pro_no'];
            $add['pro_name'] = $project['pro_name'];
            $add['admin_no'] = $adminSession['admin_no'];
            $add['admin_name'] = $adminSession['account'];
            $add['created_at'] = $add['updated_at'] = time();
            $add['chain_symbol'] = $chain['chain_symbol'];
            $add['chain_id'] = $chain['id'];
            $add['coin_name'] = $chain['chain_symbol'];
            $add['coin_symbol'] = $chain['chain_symbol'];
            $add['token_contract'] = '-';
            $add['coin_abi'] = '-';
            $add['transfer_min'] = $this->config->transfer_min[$chain['chain_symbol']];
            $add['coin_type'] = 0;
            $this->getModel('ProjectCoin')->add($add);
        }

        if($chain['chain_symbol'] == 'BTC') {
            //特殊 USDT代币
            $projectcoin = $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'], 'coin_symbol', 'USDT');
            if (!$projectcoin) {
                $add['pro_no'] = $adminSession['pro_no'];
                $add['pro_name'] = $project['pro_name'];
                $add['admin_no'] = $adminSession['admin_no'];
                $add['admin_name'] = $adminSession['account'];
                $add['created_at'] = $add['updated_at'] = time();
                $add['chain_symbol'] = $chain['chain_symbol'];
                $add['chain_id'] = $chain['id'];
                $add['coin_name'] = 'USDT';
                $add['coin_symbol'] = 'USDT';
                $add['token_contract'] = '-';
                $add['coin_abi'] = '-';
                $add['transfer_min'] = 0;
                $add['coin_type'] = 1;
                $usdtId = $this->getModel('ProjectCoin')->add($add);
            }

            //生成BTC出账钱包，新增USDT代币资产记录
            if ($type == 2) {
                $assetData = [
                    'pro_no' => $adminSession['pro_no'],
                    'pro_name' => $project['pro_name'],
                    'address' => $add['address'],
                    'chain_symbol' => 'BTC',
                    'chain_id' => $chain['id'],
                    'chain_fee_balance' => 0,
                    'coin_symbol' => 'USDT',
                    'coin_id' => $usdtId,
                    'coin_balance' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'batch_no' => '',
                ];
                $this->getModel('ProjectWalletsAssets')->add($assetData);
            }
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        $this->result['data'] = $apiForAddWallet['data'];
        return $this->result;

    }

    public function edit($id,$reqData){
        $adminSession = $this->session->get('backend');
        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].'editColdWallet',10);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请求过快';
            return $this->result;
        }

        $pro_no = $adminSession['pro_no'];
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

        $wallet = $this->getModel('ProjectWallet')->getById($id,$pro_no,$field='id,chain_symbol,chain_id,address');
        if(!$wallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '冷钱包地址记录不存在';
            return $this->result;
        }

        //查询错误次数
        $key = $pro_no.":".$admin['admin_no'].':editColdWallet:passwordError';
        $errorAccount = $this->getBussiness('RedisCache')->setError($key);
        if(!$errorAccount){
            $redisErrorAccount=0;
            $this->getBussiness('RedisCache')->editError($key,$redisErrorAccount,1800);
            $errorAccount = $redisErrorAccount;
        }

        //判断密码正确
        if($admin['password'] != md5($reqData['password'])){
            //redis错误次数+1
            $redisErrorAccount = $errorAccount+1;
            $this->getBussiness('RedisCache')->editError($key,$redisErrorAccount,1800);

            if($errorAccount >= 4){
                //将登陆的redis改为错误5次
                $this->getBussiness('RedisCache')->setLoginInfo($pro_no,$admin['name'],['errorAccount'=>5],1800);

                //发短信给超级管理员
                $superAdmin = $this->getModel('Admin')->getSuper($pro_no,$filed='phone');
                if($superAdmin){
                    //发短信
                    $sms['templateId'] = '21244';
                    $sms['phone'] = $superAdmin['phone'];
                    $sms['vars'] = '{"%adminname%":"'.$admin['name'].'","%content%":"修改冷钱包地址"}';
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


        //验证地址合法性
        $reqData['pro_no'] = $pro_no;
        $reqData['type'] = $wallet['chain_symbol'];
        if(in_array($wallet['chain_symbol'],$this->config->address_type_chain->toArray())){
            $reqData['address_type'] = $adminSession['pro_no'].'admin';
        }
        $apiForAddWallet = $this->apiForAddWallet($reqData,$type=1);
        if($apiForAddWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $apiForAddWallet['msg'];
            return $this->result;
        }

        //地址没变，更新了标签
        if($reqData['address'] == $wallet['address']){
            $data = [
                'updated_at' => time(),
                'memo' => $reqData['memo'],
            ];
            $this->getModel('ProjectWallet')->updateById($id,$data);

        }else{
            //新增冷钱包记录，并禁用之前的
            $insertData = [
                'pro_no'=>$pro_no,
                'pro_name'=>$project['pro_name'],
                'address'=>$reqData['address'],
                'wallet_type'=>1,
                'chain_id'=>$wallet['chain_id'],
                'chain_symbol'=>$wallet['chain_symbol'],
                'admin_no'=>$adminSession['admin_no'],
                'admin_name'=>$adminSession['account'],
                'memo'=>$reqData['memo'],
                'created_at'=>time(),
                'updated_at'=>time(),
            ];
            $newid = $this->getModel('ProjectWallet')->add($insertData);

            //记录日志
            $sql['pro_no'] = $adminSession['pro_no'];
            $sql['pro_name'] = $adminSession['pro_name'];
            $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_wallets';
            $sql['ip'] = $_SERVER['REMOTE_ADDR'];
            $sql['admin_no'] = $adminSession['admin_no'];
            $sql['admin_name'] = $adminSession['account'];
            $sql['created_at'] = $sql['updated_at'] = time();
            $sql['sql_type'] = 'insert';
            $sql['log_title'] = $adminSession['account'].'修改了'.$wallet['chain_symbol'].'的冷钱包地址';
            $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
            $this->getModel('AdminLog')->add($sql);



            $data = [
                'wallet_type' => 0,//无效数据
                'updated_at' => time(),
            ];
            $this->getModel('ProjectWallet')->updateById($id,$data);

        }

        $this->result['status'] = 1;
        $this->result['msg'] = '编辑成功';
        return $this->result;

    }


    //确认密码
    public function passwordConfirm($reqData){
        $coin = $this->getBussiness('ProjectCoin')->getByWhere($reqData['pro_no'],'coin_symbol','EOS');
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种不存在';
            return $this->result;
        }
        $coin = $coin[0];

        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol','EOS');
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }

        //获取出账钱包密码
        $transactionWallet = $this->getModel('ProjectWallet')->getProWallet($reqData['pro_no'],'EOS',2,$filed='*');
        if($transactionWallet){
            if(md5($reqData['password']) != $transactionWallet['password']){
                $this->result['status'] = -1;
                $this->result['msg'] = '密码错误,请重新输入';
                return $this->result;
            }
        }

        //请求钱包创建账户
        $url = $this->config->wallet_ip.'api/buyAccount';
        $walletData['type'] = 'EOS';
        $walletData['account_name'] = $reqData['address'];
        $walletData['password'] = $reqData['password'];
        $apiForAddWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
        if (!isset($apiForAddWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '钱包接口请求失败';
            return $this->result;
        }

        if($apiForAddWallet['status'] != 1){
            //超过3次创建失败
            //redis增加错误次数 24小时
            $errorCount = $this->redis->get($reqData['pro_no'].':createEosAddress');
            if(isset($errorCount)){
                $this->redis->set($reqData['pro_no'].':createEosAddress',$errorCount+1,86400);
                $this->result['data'] = $errorCount+1;
                if($errorCount < 3){
                    $this->result['msg'] = $apiForAddWallet['msg'];
                }else{
                    $this->result['msg'] = '创建失败,请联系新贝客服';
                }
            }else{
                $this->redis->set($reqData['pro_no'].':createEosAddress',1,86400);
                $this->result['msg'] = $apiForAddWallet['msg'];
                $this->result['data'] = 1;
            }
            $this->result['status'] = -1;
            return $this->result;
        }

        //记录系统后台流水flow_type=3购买账户
        $flowData = [
            'from_address'=>$this->config->from_address,
            'to_address'=>$reqData['address'],
            'chain_id'=>$chain['chain_id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'hash'=>$apiForAddWallet['data']['hash'],
            'coin_chain_amount'=>'0',
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$apiForAddWallet['data']['num'],
            'flow_type'=>3,
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('SystemServerWalletsFlow')->add($flowData);

        $this->result['status'] = 1;
        $this->result['msg'] = '创建成功';
        return $this->result;
    }


}