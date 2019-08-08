<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

use App\Libs\SendSms;

class ProjectUserWallet extends BaseBussiness
{
    public function getList($pro_no,$type){
        return $this->getModel('ProjectUserWallet')->getList($pro_no,$type,$field='*');
    }

    public function getById($id){
        return $this->getModel('ProjectUserWallet')->getById($id,$field='*');
    }

    public function getByWhere($pro_no,$whereFiled,$whereData){
        return $this->getModel('ProjectUserWallet')->getByWhere($pro_no,$whereFiled,$whereData,$filed='*');
    }

    public function updateById($id,$data){
        return $this->getModel('ProjectUserWallet')->updateById($id,$data);
    }

    //生成钱包地址
    public function add($reqData){
        $adminSession = $this->session->get('backend');
        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->addUserWalletRedisLock($adminSession['pro_no'].':'.$reqData['chain_id'].':adduserwallet');
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '该公链钱包地址正在生成中,请稍等';
            return $this->result;
        }


        $chain = $this->getModel('SystemChain')->getById($reqData['chain_id']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }


        //密码
        $reqData['password'] = $reqData['password1'].$reqData['password2'].$reqData['password3'];

        //获取项目信息
        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'id,pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //判断出账钱包
        $wallet = $this->getModel('ProjectWallet')->getByChainAndType($adminSession['pro_no'],$reqData['chain_id'],$type=2,$filed='*');
        if(!$wallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '请先设置出账钱包';
            return $this->result;
        }

        //生成批次号 币类型年月日时分秒+2位随机数 注意：使用时不能重新生成
        $batch_no = $chain['chain_symbol'].date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).$this->functions->createNo(2);

        //添加事务
        $transactionData = [
            'transaction_no' =>date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).rand(1000,9999),
            'batch_no' => $batch_no,
            'title' => '生成钱包地址',
            'coin_symbol'=>'',
            'coin_id'=>0,
            'chain_id'=>$chain['id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'count' => $reqData['count'],
            'success'=>0,
            'fail'=>0,
            'incomplete' => $reqData['count'],
            'amount'=>0,
            'pro_no' => $adminSession['pro_no'],
            'pro_name' => $project['pro_name'],
            'admin_name' => $adminSession['account'],
            'admin_no' => $adminSession['admin_no'],
            'created_at' => time(),
            'updated_at' =>time(),
        ];
        $transactionId = $this->getBussiness('ProjectTransaction')->add($transactionData);

        if(!$transactionId){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务生成失败';
            return $this->result;
        }

        //添加批次表
        $walletBatchData = [
            'address_total'=>$reqData['count'],
            'batch_no'=>$transactionData['batch_no'],
            'password_prompt'=>$reqData['password_prompt'],
            'password'=>md5($reqData['password']),
            'chain_id'=>$chain['id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'pro_no' => $adminSession['pro_no'],
            'pro_name' => $project['pro_name'],
            'admin_name' => $adminSession['account'],
            'admin_no' => $adminSession['admin_no'],
            'created_at' => time(),
            'updated_at' =>time(),
        ];
        $batch_id = $this->getBussiness('ProjectWalletBatch')->add($walletBatchData);

        if(!$batch_id){
            $this->result['status'] = -1;
            $this->result['msg'] = '批次记录生成失败';
            return $this->result;
        }

        //EOS直接生成
        if($chain['chain_symbol'] == 'EOS'){
            for($i=0;$i<$reqData['count'];$i++){
                //添加用户钱包
                $userWalletData = [
                    'batch_no'=>$walletBatchData['batch_no'],
                    'address'=>'',
                    'password_prompt'=>$reqData['password_prompt'],
                    'password'=>$reqData['password'],
                    'chain_id'=>$reqData['chain_id'],
                    'chain_symbol'=>$reqData['chain_symbol'],
                    'pro_no' => $adminSession['pro_no'],
                    'pro_name' => $project['pro_name'],
                    'admin_name' => $adminSession['account'],
                    'admin_no' => $adminSession['admin_no'],
                    'created_at' => time(),
                    'updated_at' =>time(),
                ];

                $newid = $this->getModel('ProjectUserWallet')->add($userWalletData);

                if(!$newid){
                    //更新事务表 错误次数加1
                    $sql = "UPDATE wallet_".$adminSession['pro_no']."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=".time()." WHERE id=".$transactionId;
                    $this->db->query($sql);
                }else{
                    //更新事务表
                    $sql = "UPDATE wallet_".$adminSession['pro_no']."_project_transaction SET success = `success`+1,incomplete = `incomplete`-1,updated_at=".time()." WHERE id=".$transactionId;
                    $this->db->query($sql);
                }

                //8位 项目id+记录id 16进制
                $eos_address = dechex($project['id']+15).sprintf("%06s", dechex($newid));
                $this->updateById($newid,['address'=>$eos_address,'updated_at'=>time()]);

                //存redis
                $redisData = [
                    'pro_no'=>$adminSession['pro_no'],
                    'wallet_type'=>'user',
                ];
                $this->getBussiness('RedisCache')->addWalletAddressType('EOS_address_'.$wallet['address'].'_'.$eos_address,$redisData);

                $noticeAddress[] = $eos_address;
            }

            //生成完成后将redis锁去掉
            $this->redis->del($adminSession['pro_no'].':'.$reqData['chain_id'].':adduserwallet');

            //通知项目方
            $noticeData = [
                'accounts'=>implode(',',$noticeAddress),//钱包地址(以','分隔)
                'batch_num'=>$walletBatchData['batch_no'],
                'pro_no'=>$adminSession['pro_no'],
                'type'=>$reqData['chain_symbol'],
            ];

            $reqWallet = $this->getBussiness('Api')->sendAccountInfo($noticeData);

            if (!$reqWallet) {
                $this->result['status'] = -1;
                $this->result['msg'] = '项目方通知失败';
                return $this->result;
            }

        }else{
            //请求接口 添加钱包
            $walletdata['pro_no'] = $adminSession['pro_no'];
            $walletdata['num'] = $reqData['count'];
            $walletdata['batch_no'] = $batch_no;
            $walletdata['transactionId'] = $transactionId;
            $walletdata['password'] = $reqData['password'];
            $walletdata['chain_symbol'] = $chain['chain_symbol'];//公链
            if(in_array($chain['chain_symbol'],$this->config->address_type_chain->toArray())){
                $walletdata['address_type'] = $adminSession['pro_no'].'user';
            }
            $url = $this->config->add_wallet_ip.'sh.php';
            $apiForAddWallet = $this->functions->http_request_forWallet($url,'POST',$walletdata);
            if(!$apiForAddWallet){
                $this->result['status'] = -1;
                $this->result['msg'] = '钱包接口请求失败';
                return $this->result;
            }
        }


        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_user_wallets';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'insert';
        $sqlData['log_title'] = '生成批次号为'.$walletBatchData['batch_no'].'的'.$walletBatchData['chain_symbol'].'钱包地址';
        $sqlData['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sqlData);

        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        $this->result['data'] = $batch_no;
        return $this->result;

    }


    public function addWalletNotice($reqData){
        $transaction = $this->getBussiness('ProjectTransaction')->getById($reqData['transactionId']);
        if(!$transaction){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务记录不存在';
            return $this->result;
        }

        //判断批次是否存在
        $batchWallet = $this->getBussiness('ProjectWalletBatch')->getByWhere($transaction['pro_no'],'batch_no',$reqData['batch_no']);
        if(!$batchWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '该批次记录不存在';
            return $this->result;
        }else{
            //查询该批次是否存在地址
            $useraddress = $this->getByWhere($transaction['pro_no'],'batch_no',$reqData['batch_no']);
            if($useraddress){
                $this->result['status'] = -1;
                $this->result['msg'] = '该批次地址已生成，请勿重复操作';
                return $this->result;
            }

            $batchWallet = $batchWallet[0];
        }

        if($reqData['status'] == -1){
            //将生成的redis锁去掉
            $this->redis->del($batchWallet['pro_no'].':'.$batchWallet['chain_id'].':adduserwallet');

        }else{
            $address = explode(',',$reqData['address']);
            foreach ($address as $v){
                //添加用户钱包
                $userWalletData = [
                    'batch_no'=>$batchWallet['batch_no'],
                    'address'=>$v,
                    'password_prompt'=>$batchWallet['password_prompt'],
                    'password'=>$batchWallet['password'],
                    'chain_id'=>$batchWallet['chain_id'],
                    'chain_symbol'=>$batchWallet['chain_symbol'],
                    'pro_no' => $batchWallet['pro_no'],
                    'pro_name' => $batchWallet['pro_name'],
                    'admin_name' => $batchWallet['admin_name'],
                    'admin_no' => $batchWallet['admin_no'],
                    'created_at' => time(),
                    'updated_at' =>time(),
                ];

                $newid = $this->getModel('ProjectUserWallet')->add($userWalletData);

                if(!$newid){
                    //更新事务表 错误次数加1
                    $sql = "UPDATE wallet_".$batchWallet['pro_no']."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=".time()." WHERE id=".$reqData['transactionId'];
                    $this->db->query($sql);
                }else{
                    //更新事务表
                    $sql = "UPDATE wallet_".$batchWallet['pro_no']."_project_transaction SET success = `success`+1,incomplete = `incomplete`-1,updated_at=".time()." WHERE id=".$reqData['transactionId'];
                    $this->db->query($sql);
                }
                //存redis
//                //EOS
//                if($batchWallet['chain_symbol'] == 'EOS'){
//                    $v = 'EOS_address_'.$v;
//                }

                $redisData = [
                    'pro_no'=>$batchWallet['pro_no'],
                    'wallet_type'=>'user',
                ];
                $this->getBussiness('RedisCache')->addWalletAddressType($v,$redisData);


            }

            //将生成的redis锁去掉
            $this->redis->del($batchWallet['pro_no'].':'.$batchWallet['chain_id'].':adduserwallet');


            //通知项目方
            $noticeData = [
                'accounts'=>$reqData['address'],//钱包地址(以','分隔)
                'batch_num'=>$reqData['batch_no'],
                'pro_no'=>$batchWallet['pro_no'],
                'type'=>$reqData['chain_symbol'],
            ];

            $reqWallet = $this->getBussiness('Api')->sendAccountInfo($noticeData);

            if (!$reqWallet) {
                $this->result['status'] = -1;
                $this->result['msg'] = '项目方通知失败';
                return $this->result;
            }
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '后台通知成功';
        return $this->result;
    }


    //转出冷钱包
    public function transfer($batch_id,$reqData){
        $adminSession = $this->session->get('backend');
        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':transfer',10);
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

        //判断批次是否存在
        $batchWallet = $this->getBussiness('ProjectWalletBatch')->getById($batch_id);
        if(!$batchWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '该批次记录不存在';
            return $this->result;
        }


        //如果有正在进行中的转出历史，则不可再转出
        $transaction_list = $this->db->query("select count(id) as allcount from wallet_".$pro_no."_project_transaction where pro_no = '".$adminSession['pro_no']."' and batch_no = '".$batchWallet['batch_no']."' and title='转出冷钱包' and status = 0");
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

        //EOS
        if($batchWallet['chain_symbol'] != 'EOS'){
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
        }else{
            //获取出账钱包
            $wallet = $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$batchWallet['chain_id'],$type=2,$filed='*');
            if(!$wallet){
                $this->result['status'] = -1;
                $this->result['msg'] = '请先设置出账钱包';
                return $this->result;
            }
        }


        //判断资产是否为0
        $sql = "select sum(coin_balance) as allamount from wallet_".$pro_no."_project_wallets_assets where pro_no='".$pro_no."' and batch_no='".$batchWallet['batch_no']."'";
        $assetAmount = $this->db->query($sql);
        $assetAmount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $assetAmount = $assetAmount->fetch();
        if($assetAmount['allamount'] == 0){
            $this->result['status'] = -1;
            $this->result['msg'] = '此批次的地址资产为0，无法转出';
            return $this->result;
        }

        //密码
        $reqData['password'] = $reqData['password1'].$reqData['password2'].$reqData['password3'];

        //查询错误次数
        $key = $pro_no.":".$adminSession['admin_no'].':transferColdWallet:passwordError';
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
                    $sms['vars'] = '{"%adminname%":"'.$adminSession['account'].'","%content%":"转出冷钱包"}';
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

        if($batchWallet['chain_symbol'] == 'EOS'){
            //获取出账钱包密码
            $transactionWallet = $this->getModel('ProjectWallet')->getProWallet($pro_no,'EOS',2,$filed='*');
            if($transactionWallet){
                if(md5($reqData['transferwallet_password']) != $transactionWallet['password']){
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
                            $sms['vars'] = '{"%adminname%":"'.$adminSession['account'].'","%content%":"转出冷钱包"}';
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
            }
        }


        $coins = $this->getBussiness('ProjectCoin')->getByWhere($pro_no,'chain_id',$batchWallet['chain_id']);
        $transfer_mins = array();
        if($coins){
            foreach ($coins as $coinValue){
                $transfer_mins[$coinValue['coin_symbol']]['transfer_min'] = $coinValue['transfer_min'];
            }
        }


        //所有钱包（资产表,资产大于0）
//        $userWalletAsset = $this->getBussiness('ProjectWalletAsset')->getByWhere($pro_no,'batch_no',$batchWallet['batch_no']);
        $userWalletAssetSql = "select * from wallet_".$pro_no."_project_wallets_assets where pro_no = '".$pro_no."' and batch_no = '".$batchWallet['batch_no']."' and coin_balance > 0";
        $userWalletAsset = $this->db->query($userWalletAssetSql);
        $userWalletAsset->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $userWalletAsset = $userWalletAsset->fetchAll();

        if($userWalletAsset){
            //拼接数据生成事务
            $transaction_no = date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).rand(1000,9999);
            $transactionDatas = array();

            //EOS
            if($batchWallet['chain_symbol'] == 'EOS'){
                $eosWalletAsset['password'] = $reqData['transferwallet_password'];
                $eosWalletAsset['transaction_no'] = $transaction_no;
                $eosWalletAsset['admin_no'] = $admin['admin_no'];
                $eosWalletAsset['admin_name'] = $admin['name'];
                $eosWalletAsset['coin_balance'] = 0;
                $eosWalletAsset['address'] = $wallet['address'];//出账钱包总账户

                foreach ($userWalletAsset as $k=>$v){
                    if($v['coin_balance'] < $transfer_mins[$v['coin_symbol']]['transfer_min']){
                        unset($userWalletAsset[$k]);
                        continue;
                    }
                    //队列里给钱包接口
                    $eosWalletAsset['coin_balance'] += sprintf("%.4f",$v['coin_balance']);

                    if (isset($transactionDatas[$v['coin_symbol']])){
                        if($v['coin_balance'] < $transfer_mins[$v['coin_symbol']]['transfer_min']){
                            continue;
                        }
                        $transactionDatas[$v['coin_symbol']]['coin_id'] = $v['coin_id'];
                        $transactionDatas[$v['coin_symbol']]['num'] += 1;
                        $transactionDatas[$v['coin_symbol']]['amount'] += $v['coin_balance'];
                    }else{
                        if($v['coin_balance'] < $transfer_mins[$v['coin_symbol']]['transfer_min']){
                            continue;
                        }
                        $transactionDatas[$v['coin_symbol']] = ['num'=>0,'amount'=>0];
                        $transactionDatas[$v['coin_symbol']]['coin_id'] = $v['coin_id'];
                        $transactionDatas[$v['coin_symbol']]['num'] += 1;
                        $transactionDatas[$v['coin_symbol']]['amount'] += $v['coin_balance'];
                    }

                    $eosWalletAsset['wallet_count'] = $transactionDatas[$v['coin_symbol']]['num'];
                }
                unset($userWalletAsset[0]['coin_balance']);
                unset($userWalletAsset[0]['address']);
                $userEOSWalletAsset[0] = array_merge($eosWalletAsset,$userWalletAsset[0]);

            }else{
                foreach ($userWalletAsset as $k=>$v){
                    if($v['coin_balance'] < $transfer_mins[$v['coin_symbol']]['transfer_min']){
                        unset($userWalletAsset[$k]);
                        continue;
                    }

                    //队列里给钱包接口
                    $userWalletAsset[$k]['password'] = $reqData['password'];
                    $userWalletAsset[$k]['transaction_no'] = $transaction_no;
                    $userWalletAsset[$k]['admin_no'] = $admin['admin_no'];
                    $userWalletAsset[$k]['admin_name'] = $admin['name'];
                    $userWalletAsset[$k]['coin_balance'] += sprintf("%.4f",$v['coin_balance']);

                    if (isset($transactionDatas[$v['coin_symbol']])){
                        if($v['coin_balance'] < $transfer_mins[$v['coin_symbol']]['transfer_min']){
                            continue;
                        }
                        $transactionDatas[$v['coin_symbol']]['coin_id'] = $v['coin_id'];
                        $transactionDatas[$v['coin_symbol']]['num'] += 1;
//                    $transactionDatas[$v['coin_symbol']]['amount'] += $v['coin_balance'];
                    }else{
                        if($v['coin_balance'] < $transfer_mins[$v['coin_symbol']]['transfer_min']){
                            continue;
                        }
                        $transactionDatas[$v['coin_symbol']] = ['num'=>0,'amount'=>0];
                        $transactionDatas[$v['coin_symbol']]['coin_id'] = $v['coin_id'];
                        $transactionDatas[$v['coin_symbol']]['num'] += 1;
//                    $transactionDatas[$v['coin_symbol']]['amount'] += $v['coin_balance'];
                    }
                }
            }


            //批次里的每一个都不满足最小值
            if($userWalletAsset == []){
                $this->result['status'] = -1;
                $this->result['msg'] = '未达到转出冷钱包最小值';
                return $this->result;
            }

            //转出历史里的单条事务
            $sqlData['sql'] = '';
            foreach ($transactionDatas as $k=>$data){
                $transactionInfo = [
                    'transaction_no' => $transaction_no,
                    'batch_no' => $batchWallet['batch_no'],
                    'title' => '转出冷钱包',
                    'coin_symbol'=>$k,
                    'coin_id'=>$data['coin_id'],
                    'chain_id'=>$batchWallet['chain_id'],
                    'chain_symbol'=>$batchWallet['chain_symbol'],
                    'count' => $data['num'],
                    'success'=>0,
                    'fail'=>0,
                    'incomplete' => $data['num'],
                    'amount'=>($batchWallet['chain_symbol'] == 'EOS')?$data['amount']:'0',
//                    'amount'=>0,
                    'pro_no' => $adminSession['pro_no'],
                    'pro_name' => $project['pro_name'],
                    'admin_name' => $adminSession['account'],
                    'admin_no' => $adminSession['admin_no'],
                    'created_at' => time(),
                    'updated_at' =>time(),
                ];

                $this->getBussiness('ProjectTransaction')->add($transactionInfo);

                $sqlData['sql'].= $this->di->get('profiler')->getLastProfile()->getSQLStatement().',';
            }

            if($batchWallet['chain_symbol'] == 'EOS'){
                //地址存入redis
                $this->getBussiness('RedisCache')->lpushTransferColdWallet($userEOSWalletAsset);
            }else{
                //地址存入redis
                $this->getBussiness('RedisCache')->lpushTransferColdWallet($userWalletAsset);
            }


        }else{
            $this->result['status'] = -1;
            $this->result['msg'] = '此批次的地址资产为0，无法转出';
            return $this->result;
        }




        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_transaction';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'insert';
        $sqlData['log_title'] = '转出冷钱包('.$batchWallet['batch_no'].')';

        $this->getModel('AdminLog')->add($sqlData);


        $this->result['status'] = 1;
        $this->result['msg'] = '转出成功';
        return $this->result;
    }

    //失败重转
    public function transferSecond($transactionandbatch,$reqData){
        $transaction_no = explode(',',$transactionandbatch)[0];
        $batch_no = explode(',',$transactionandbatch)[1];

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];
        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':transfer',10);
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
        $batchWallet = $this->getBussiness('ProjectWalletBatch')->getByWhere($pro_no,'batch_no',$batch_no);
        if(!$batchWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '该批次记录不存在';
            return $this->result;
        }else{
            $batchWallet = $batchWallet[0];
        }

        //判断是否有冷钱包地址和手续费钱包地址,以及手续费钱包是否有钱
        $lengWallet = $this->getBussiness('ProjectWallet')->getByChainAndType($pro_no,$batchWallet['chain_id'],1);
        if(!$lengWallet){
            $this->result['status'] = -3;
            $this->result['msg'] = '请先设置冷钱包';
            return $this->result;
        }

        //EOS
        if($batchWallet['chain_symbol'] != 'EOS'){
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
        }else{
            //获取出账钱包
            $wallet = $this->getModel('ProjectWallet')->getByChainAndType($pro_no,$batchWallet['chain_id'],$type=2,$filed='*');
            if(!$wallet){
                $this->result['status'] = -1;
                $this->result['msg'] = '请先设置出账钱包';
                return $this->result;
            }
        }

        //密码
        $reqData['password'] = $reqData['password1'].$reqData['password2'].$reqData['password3'];
        //查询错误次数
        $key = $pro_no.":".$adminSession['admin_no'].':second:transferColdWallet:passwordError';
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
                    $sms['vars'] = '{"%adminname%":"'.$adminSession['account'].'","%content%":"失败重新转出冷钱包"}';
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

        if($batchWallet['chain_symbol'] == 'EOS'){
            //获取出账钱包密码
            $transactionWallet = $this->getModel('ProjectWallet')->getProWallet($pro_no,'EOS',2,$filed='*');
            if($transactionWallet){
                if(md5($reqData['transferwallet_password']) != $transactionWallet['password']) {
                    //redis错误次数+1
                    $redisErrorAccount = $errorAccount + 1;
                    $this->getBussiness('RedisCache')->editError($key, $redisErrorAccount, 1800);

                    if ($errorAccount >= 4) {
                        //将登陆的redis改为错误5次
                        $this->getBussiness('RedisCache')->setLoginInfo($pro_no, $adminSession['account'], ['errorAccount' => 5], 1800);

                        //发短信给超级管理员
                        $superAdmin = $this->getModel('Admin')->getSuper($pro_no, $filed = 'phone');
                        if ($superAdmin) {
                            //发短信
                            $sms['templateId'] = '21244';
                            $sms['phone'] = $superAdmin['phone'];
                            $sms['vars'] = '{"%adminname%":"' . $adminSession['account'] . '","%content%":"失败重新转出冷钱包"}';
                            $SendSms = new SendSms();
                            $SendSms->send_sms($sms);
                        }

                        //退出登陆
                        $this->result['status'] = -2;
                        $this->result['msg'] = '密码错误五次,请三十分钟后再试';
                        return $this->result;

                    }
                }
            }
        }



        //查找失败的事务详情
        $sql = "select * from wallet_".$pro_no."_project_transaction_info where status = -1 and transaction_no = '".$transaction_no."' ";
        $transactionInfo = $this->db->query($sql);
        $transactionInfo->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $transactionInfo = $transactionInfo->fetchAll();

        //EOS
        if($batchWallet['chain_symbol'] == 'EOS'){
            //判断资产是否为0
            $asset = 0;
            foreach ($transactionInfo as $v){
                //计算资产
                $sql = "select coin_balance from wallet_".$pro_no."_project_wallets_assets where pro_no='".$pro_no."' and address='".$v['wallet_address']."' and coin_id=".$v['coin_id'];
                $assetAmount = $this->db->query($sql);
                $assetAmount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                $assetAmount = $assetAmount->fetch();
                $asset = $asset + $assetAmount['coin_balance'];

                //拼接所有的要存入redis的资产数据
                $sql = "select * from wallet_".$pro_no."_project_wallets_assets where pro_no='".$pro_no."' and address='".$v['wallet_address']."' and coin_id=".$v['coin_id'];
                $assetInfo = $this->db->query($sql);
                $assetInfo->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                $assetInfo = $assetInfo->fetchAll();
                $userWalletAsset[] = $assetInfo;

            }

            $userWalletAsset = $this->functions->mergeArr($userWalletAsset);

            if($asset == 0){
                $this->result['status'] = -1;
                $this->result['msg'] = '此批次的地址资产为0，无法转出';
                return $this->result;
            }

            $eosWalletAsset['password'] = $reqData['transferwallet_password'];
            $eosWalletAsset['transaction_no'] = $transaction_no;
            $eosWalletAsset['admin_no'] = $admin['admin_no'];
            $eosWalletAsset['admin_name'] = $admin['name'];
            $eosWalletAsset['coin_balance'] = 0;
            $eosWalletAsset['address'] = $wallet['address'];
            foreach ($userWalletAsset as $k=>$item){
                $eosWalletAsset['coin_balance'] += sprintf("%.4f",$item['coin_balance']);
            }

            unset($userWalletAsset[0]['coin_balance']);
            unset($userWalletAsset[0]['address']);
            $userEOSWalletAsset[0] = array_merge($eosWalletAsset,$userWalletAsset[0]);

            $sqlData['sql'] = '';
            foreach ($transactionInfo as $v) {
                //事务里的失败改成0,进行中
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = 0,status = 0,updated_at=" . time() . " WHERE transaction_no='" . $transaction_no . "' and coin_id='" . $v['coin_id'] . "'";
                $this->db->query($sql);

                //将详情里的失败状态改成待处理
                $sql = "UPDATE wallet_".$pro_no."_project_transaction_info SET status = 0,updated_at=" . time() . " WHERE transaction_no='" . $transaction_no . "' and batch_no = '".$v['batch_no']."' and coin_id=".$v['coin_id'];
                $this->db->query($sql);

                $sqlData['sql'].= $this->di->get('profiler')->getLastProfile()->getSQLStatement().',';

            }


            //记录日志
            $sqlData['pro_no'] = $adminSession['pro_no'];
            $sqlData['pro_name'] = $adminSession['pro_name'];
            $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_transaction';
            $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
            $sqlData['admin_no'] = $adminSession['admin_no'];
            $sqlData['admin_name'] = $adminSession['account'];
            $sqlData['created_at'] = $sqlData['updated_at'] = time();
            $sqlData['sql_type'] = 'insert';
            $sqlData['log_title'] = '转出冷钱包失败重转('.$batchWallet['batch_no'].')';

            $this->getModel('AdminLog')->add($sqlData);
        }else{
            //判断资产是否为0
            $asset = 0;
            foreach ($transactionInfo as $v){
                //计算资产
                $sql = "select coin_balance from wallet_".$pro_no."_project_wallets_assets where pro_no='".$pro_no."' and address='".$v['wallet_address']."' and coin_id=".$v['coin_id'];
                $assetAmount = $this->db->query($sql);
                $assetAmount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                $assetAmount = $assetAmount->fetch();
                $asset = $asset + $assetAmount['coin_balance'];

                //拼接所有的要存入redis的资产数据
                $sql = "select * from wallet_".$pro_no."_project_wallets_assets where pro_no='".$pro_no."' and address='".$v['wallet_address']."' and coin_id=".$v['coin_id'];
                $assetInfo = $this->db->query($sql);
                $assetInfo->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                $assetInfo = $assetInfo->fetchAll();
                $userWalletAsset[] = $assetInfo;

            }

            $userWalletAsset = $this->functions->mergeArr($userWalletAsset);

            if($asset == 0){
                $this->result['status'] = -1;
                $this->result['msg'] = '此批次的地址资产为0，无法转出';
                return $this->result;
            }

            foreach ($userWalletAsset as $k=>$item){
                $userWalletAsset[$k]['password'] = $reqData['password'];
                $userWalletAsset[$k]['transaction_no'] = $transaction_no;
                $userWalletAsset[$k]['admin_no'] = $admin['admin_no'];
                $userWalletAsset[$k]['admin_name'] = $admin['name'];
                $userWalletAsset[$k]['coin_balance'] = sprintf("%.4f",$item['coin_balance']);
            }
//        dd($userWalletAsset);


            $sqlData['sql'] = '';
            foreach ($transactionInfo as $v) {
                //事务里的失败改成0,进行中
                $sql = "UPDATE wallet_".$pro_no."_project_transaction SET fail = 0,status = 0,updated_at=" . time() . " WHERE transaction_no='" . $transaction_no . "' and coin_id='" . $v['coin_id'] . "'";
                $this->db->query($sql);

                //将详情里的失败状态改成待处理
                $sql = "UPDATE wallet_".$pro_no."_project_transaction_info SET status = 0,updated_at=" . time() . " WHERE transaction_no='" . $transaction_no . "' and wallet_address = '".$v['wallet_address']."' and coin_id=".$v['coin_id'];
                $this->db->query($sql);

                $sqlData['sql'].= $this->di->get('profiler')->getLastProfile()->getSQLStatement().',';

            }


            //记录日志
            $sqlData['pro_no'] = $adminSession['pro_no'];
            $sqlData['pro_name'] = $adminSession['pro_name'];
            $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_transaction';
            $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
            $sqlData['admin_no'] = $adminSession['admin_no'];
            $sqlData['admin_name'] = $adminSession['account'];
            $sqlData['created_at'] = $sqlData['updated_at'] = time();
            $sqlData['sql_type'] = 'insert';
            $sqlData['log_title'] = '转出冷钱包失败重转('.$batchWallet['batch_no'].')';

            $this->getModel('AdminLog')->add($sqlData);

        }

        if($batchWallet['chain_symbol'] == 'EOS'){
            //地址存入redis
            $this->getBussiness('RedisCache')->lpushSecondTransferColdWallet($userEOSWalletAsset);
        }else{
            //地址存入redis
            $this->getBussiness('RedisCache')->lpushSecondTransferColdWallet($userWalletAsset);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '转出成功';
        return $this->result;
    }


    public function addFeeNotice($data){
        //获取项目信息
        $project = $this->getModel('Project')->getDetail($data['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //修改错误原因
        $sql = "UPDATE wallet_".$data['pro_no']."_project_transaction_info SET remark = '".$data['remark']."',updated_at=".time()." WHERE transaction_no='".$data['transaction_no']."' and coin_symbol='".$data['coin_symbol']."' and wallet_address='".$data['address']."'";
        $this->db->query($sql);


        //status -1 手续费钱包余额不足 -2手续费钱包密码错误
        if($data['status'] == -1){
            //发短信给超级管理员
            $superAdmin = $this->getModel('Admin')->getSuper($data['pro_no'],$filed='phone');
            if(!$superAdmin){
                $this->result['status'] = -1;
                $this->result['msg'] = '暂无超级管理员';
                return $this->result;
            }else{
                //发短信
                $sms['templateId'] = '22648';
                $sms['phone'] = $superAdmin['phone'];
                $sms['vars'] = '{"%chain_symbol%":"'.$data['chain_symbol'].'"}';
                $SendSms = new SendSms();
                $SendSms->send_sms($sms);
            }
        }


        $this->result['status'] = 1;
        $this->result['msg'] = '通知成功';
        return $this->result;
    }

}