<?php

class SecondtransfertopurseTask extends \Phalcon\Cli\Task
{
    /**
     * @param array $params
     */
    public function transferAction(array $params)
    {
        While(true) {
            try {

                $this->updateMysqlConnect();

                $requestListKey = 'wallet:second:transfer';
                $userWallet = $this->redis->rpop($requestListKey);
                if (!$userWallet){
                    sleep(2);
                    continue;
                }
                $userWallet = json_decode($userWallet, true);
                //redis数据
                foreach ($userWallet as $item) {
                    //设定结束后要给钱包的参数
                    $batch_no = $item['batch_no'];
                    $type = $item['chain_symbol'];
                    $pro_no = $item['pro_no'];
                    $transaction_no = $item['transaction_no'];

                    $feeWallet = $this->getDI()->getShared('db')->query("select address,password from wallet_".$item['pro_no']."_project_wallets where pro_no='" . $item['pro_no'] . "' and chain_id='" . $item['chain_id'] . "' and wallet_type=3");
                    $feeWallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                    $feeWallet = $feeWallet->fetch();
                    $lengWallet = $this->getDI()->getShared('db')->query("select address,password,memo from wallet_".$item['pro_no']."_project_wallets where pro_no='" . $item['pro_no'] . "' and chain_id='" . $item['chain_id'] . "' and wallet_type=1");
                    $lengWallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                    $lengWallet = $lengWallet->fetch();
                    //查询coin
                    $coin = $this->getDI()->getShared('db')->query("select coin_abi,token_contract from wallet_".$item['pro_no']."_project_coin where id='" . $item['coin_id'] . "'");
                    $coin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                    $coin = $coin->fetch();

                    $url = $this->config->wallet_ip . 'api/financeWithdrawl';
                    //请求钱包数据
                    $walletData = [
                        'type' => $item['chain_symbol'],
                        'coin_type' => $item['coin_symbol'],
                        'batch_no' => $item['batch_no'],
                        'address' => $item['address'],
                        'abi' => $coin['coin_abi'],
                        'contract_address' => $coin['token_contract'],
                        'pro_no' => $item['pro_no'],
                        'transaction_no' => $item['transaction_no'],
                        'fee_wallet_address' => $feeWallet['address']?$feeWallet['address']:'fee_wallet_address',
                        'fee_wallet_password' => $feeWallet['password']?$feeWallet['password']:'fee_wallet_password',
                        'leng' => $lengWallet['address'],
                        'memo' => $lengWallet['memo'],
                        'num' => $item['coin_balance'],
                        'password' => $item['password'],//redis存的用户真实密码
                    ];

                    var_dump($walletData);
                    $reqWallet = $this->functions->http_request_forWallet($url, 'POST', $walletData);
                    var_dump($reqWallet);
                    if (!isset($reqWallet)) {
                        if($item['chain_symbol'] == 'EOS'){
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET fail = `count`,status=0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_id='" . $item['coin_id'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            //详情
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction_info SET remark='失败重转请求失败',status = -1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and batch_no = '".$item['batch_no']."' and coin_id=".$item['coin_id'];
                            $this->getDI()->getShared('db')->query($sql);
                            var_dump('eos_fail');
                            continue;
                        }else{
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET fail = `fail`+1,status=0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_id='" . $item['coin_id'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            //详情
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction_info SET remark='失败重转请求失败',status = -1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and wallet_address = '".$item['address']."' and coin_id=".$item['coin_id'];
                            $this->getDI()->getShared('db')->query($sql);
                            continue;
                        }
                    }

                    if ($reqWallet['status'] != 1) {
                        if($item['chain_symbol'] == 'EOS'){
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET fail = `count`,status=0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_id='" . $item['coin_id'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            //详情
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction_info SET remark='".$reqWallet['msg']."',status = -1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and batch_no = '".$item['batch_no']."' and coin_id=".$item['coin_id'];
                            $this->getDI()->getShared('db')->query($sql);
                            var_dump($reqWallet['msg']);
                            continue;
                        }else{
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET fail = `fail`+1,status=0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_id='" . $item['coin_id'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            //详情
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction_info SET remark='".$reqWallet['msg']."',status = -1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and wallet_address = '".$item['address']."' and coin_id=".$item['coin_id'];
                            $this->getDI()->getShared('db')->query($sql);
                            var_dump($reqWallet['msg']);
                            continue;
                        }
                    }

                    if ($reqWallet['status'] == 1) {
                        if($item['chain_symbol'] == 'EOS'){
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET amount = `amount`+".$reqWallet['data']['num'].",status=0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_id='" . $item['coin_id'] . "'";
                            var_dump($sql);
                            $this->getDI()->getShared('db')->query($sql);
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction_info SET hash='".$reqWallet['data']['hash']."', status = 0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and batch_no='".$item['batch_no']."' and coin_id='" . $item['coin_id'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                        }else{
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET amount = `amount`+".$reqWallet['data']['num'].",status=0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_id='" . $item['coin_id'] . "'";
                            var_dump($sql);
                            $this->getDI()->getShared('db')->query($sql);
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction_info SET hash='".$reqWallet['data']['hash']."',amount='".$reqWallet['data']['num']."', status = 0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and wallet_address='".$item['address']."' and coin_id='" . $item['coin_id'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                        }

                        var_dump('成功');
                    }
                }

                var_dump('通知结束');
                //请求钱包通知已完成
                $url = $this->config->wallet_ip.'api/financeWithdrawlOver';
                //请求钱包数据
                $walletOverData['type'] = $type;
                $walletOverData['batch_no'] = $batch_no;
                $walletOverData['transaction_no'] = $transaction_no;
                $walletOverData['pro_no'] = $pro_no;
                var_dump($walletOverData);
                var_dump($this->functions->http_request_forWallet($url, 'POST', $walletOverData));


            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }


    public function updateMysqlConnect(){
        $key = "wallet-system-admin:mysql:reconnect:SecondtransfertopurseTask";

        //如果存在
        if($this->redis->get($key) == 1){
            return true;
        }else{
            //设置过期时间
            $this->redis->setex($key,60*60*1,1);
            //重连mysql
            $this->dbConnectAgain();
            var_dump('SecondtransfertopurseTask update Mysql connect');
            return true;
        }
    }


    //数据库重连
    public function dbConnectAgain(){

        $this->getDI()->getShared('db')->close();
        $this->getDI()->remove('db');
        //$mysqlConfig = $this->config['database']['db'];
        $this->getDI()->setShared('db', function (){
            $config = $this->getConfig();
            $params = [
                'host'     => $config->database->host,
                'username' => $config->database->username,
                'password' => $config->database->password,
                'dbname'   => $config->database->dbname,
                'charset'  => $config->database->charset
            ];
            return new \Phalcon\Db\Adapter\Pdo\Mysql($params);
        });
        //此条sql没有做容错处理，会在最外层抓取错误
//                $result = $this->getDI()->getShared('db')->query($sql);
//                return $result;
    }
}