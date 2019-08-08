<?php

class TransfertopurseTask extends \Phalcon\Cli\Task
{
    /**
     * @param array $params
     */
    public function transferAction(array $params){

        While(true) {

            try {

                $this->updateMysqlConnect();

                $requestListKey = 'wallet:transfer';
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
                    $transaction_no = $item['transaction_no'];
                    $pro_no = $item['pro_no'];
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

                    //资产
                    $userWalletAsset = $this->getDI()->getShared('db')->query("select * from wallet_".$item['pro_no']."_project_wallets_assets where pro_no='" . $item['pro_no'] . "' and batch_no='" . $item['batch_no'] . "'");
                    $userWalletAsset->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                    $userWalletAsset = $userWalletAsset->fetchAll();

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
                        //EOS
                        if ($item['chain_symbol'] == 'EOS') {
                            //更新事务表 错误次数加1
                            $sql = "UPDATE wallet_" . $item['pro_no'] . "_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_symbol='" . $item['coin_symbol'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
//                            for ($i = 0; $i < $item['wallet_count']; $i++) {
                            foreach ($userWalletAsset as $assetValue){
                                if($assetValue['coin_balance'] == 0){
                                    continue;
                                }
                                $sql = "insert into wallet_" . $item['pro_no'] . "_project_transaction_info (`transaction_no`,`batch_no`,`coin_symbol`,`coin_id`,`chain_id`,`chain_symbol`,`wallet_address`,`pro_no`,`pro_name`,`admin_no`,`admin_name`,`status`,`remark`,`created_at`,`updated_at`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                                $transactionInfoData = [
                                    $item['transaction_no'],
                                    $item['batch_no'],
                                    $item['coin_symbol'],
                                    $item['coin_id'],
                                    $item['chain_id'],
                                    $item['chain_symbol'],
                                    $item['address'],
                                    $item['pro_no'],
                                    $item['pro_name'],
                                    $item['admin_no'],
                                    $item['admin_name'],
                                    -1,
                                    '请求失败',
                                    time(),
                                    time(),
                                    $assetValue['coin_balance'],
                                    $assetValue['address']
                                ];
                                $this->getDI()->getShared('db')->query($sql, $transactionInfoData);
                            }

                        } else {
                            $sql = "UPDATE wallet_" . $item['pro_no'] . "_project_transaction SET fail = `count`,incomplete = 0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_symbol='" . $item['coin_symbol'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            $sql = "insert into wallet_" . $item['pro_no'] . "_project_transaction_info (`transaction_no`,`batch_no`,`coin_symbol`,`coin_id`,`chain_id`,`chain_symbol`,`wallet_address`,`pro_no`,`pro_name`,`admin_no`,`admin_name`,`status`,`remark`,`created_at`,`updated_at`,`amount`,`wallet_address`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            $transactionInfoData = [
                                $item['transaction_no'],
                                $item['batch_no'],
                                $item['coin_symbol'],
                                $item['coin_id'],
                                $item['chain_id'],
                                $item['chain_symbol'],
                                $item['address'],
                                $item['pro_no'],
                                $item['pro_name'],
                                $item['admin_no'],
                                $item['admin_name'],
                                -1,
                                '请求失败',
                                time(),
                                time(),
                            ];
                            $this->getDI()->getShared('db')->query($sql, $transactionInfoData);
                        }

                        var_dump('失败');
                        continue;
                    }

                    if ($reqWallet['status'] != 1) {
                        //EOS
                        if($item['chain_symbol'] == 'EOS'){
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET fail = `count`,incomplete = 0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_symbol='" . $item['coin_symbol'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
//                            for($i=0;$i<$item['wallet_count'];$i++) {
                            foreach ($userWalletAsset as $assetValue){
                                if($assetValue['coin_balance'] == 0){
                                    continue;
                                }

                                $sql = "insert into wallet_" . $item['pro_no'] . "_project_transaction_info (`transaction_no`,`batch_no`,`coin_symbol`,`coin_id`,`chain_id`,`chain_symbol`,`pro_no`,`pro_name`,`admin_no`,`admin_name`,`status`,`remark`,`created_at`,`updated_at`,`amount`,`wallet_address`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                                $transactionInfoData = [
                                    $item['transaction_no'],
                                    $item['batch_no'],
                                    $item['coin_symbol'],
                                    $item['coin_id'],
                                    $item['chain_id'],
                                    $item['chain_symbol'],
                                    $item['pro_no'],
                                    $item['pro_name'],
                                    $item['admin_no'],
                                    $item['admin_name'],
                                    -1,
                                    $reqWallet['msg'],
                                    time(),
                                    time(),
                                    $assetValue['coin_balance'],
                                    $assetValue['address']
                                ];
                                $this->getDI()->getShared('db')->query($sql, $transactionInfoData);
                            }
                        }else{
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET fail = `fail`+1,incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_symbol='" . $item['coin_symbol'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            $sql = "insert into wallet_".$item['pro_no']."_project_transaction_info (`transaction_no`,`batch_no`,`coin_symbol`,`coin_id`,`chain_id`,`chain_symbol`,`wallet_address`,`pro_no`,`pro_name`,`admin_no`,`admin_name`,`status`,`remark`,`created_at`,`updated_at`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            $transactionInfoData = [
                                $item['transaction_no'],
                                $item['batch_no'],
                                $item['coin_symbol'],
                                $item['coin_id'],
                                $item['chain_id'],
                                $item['chain_symbol'],
                                $item['address'],
                                $item['pro_no'],
                                $item['pro_name'],
                                $item['admin_no'],
                                $item['admin_name'],
                                -1,
                                $reqWallet['msg'],
                                time(),
                                time(),
                            ];
                            $this->getDI()->getShared('db')->query($sql, $transactionInfoData);
                            var_dump($sql);
                            var_dump($transactionInfoData);
                        }

                        continue;
                    }

                    if ($reqWallet['status'] == 1) {
                        //EOS
                        if($item['chain_symbol'] == 'EOS'){
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET amount = ".$reqWallet['data']['num'].",incomplete = 0,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_symbol='" . $item['coin_symbol'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
//                            for($i=0;$i<$item['wallet_count'];$i++){
                            foreach ($userWalletAsset as $assetValue){
                                if($assetValue['coin_balance'] == 0){
                                    continue;
                                }
                                $sql = "insert into wallet_".$item['pro_no']."_project_transaction_info (`transaction_no`,`batch_no`,`coin_symbol`,`coin_id`,`chain_id`,`chain_symbol`,`hash`,`pro_no`,`pro_name`,`admin_no`,`admin_name`,`status`,`created_at`,`updated_at`,`amount`,`wallet_address`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                                $transactionInfoData = [
                                    $item['transaction_no'],
                                    $item['batch_no'],
                                    $item['coin_symbol'],
                                    $item['coin_id'],
                                    $item['chain_id'],
                                    $item['chain_symbol'],
                                    $reqWallet['data']['hash'],
                                    $item['pro_no'],
                                    $item['pro_name'],
                                    $item['admin_no'],
                                    $item['admin_name'],
                                    0,
                                    time(),
                                    time(),
                                    $assetValue['coin_balance'],
                                    $assetValue['address']
                                ];
                                $this->getDI()->getShared('db')->query($sql, $transactionInfoData);
                            }
                        }else{
                            $sql = "UPDATE wallet_".$item['pro_no']."_project_transaction SET amount = `amount`+".$reqWallet['data']['num'].",incomplete = `incomplete`-1,updated_at=" . time() . " WHERE transaction_no='" . $walletData['transaction_no'] . "' and coin_symbol='" . $item['coin_symbol'] . "'";
                            $this->getDI()->getShared('db')->query($sql);
                            $sql = "insert into wallet_".$item['pro_no']."_project_transaction_info (`transaction_no`,`batch_no`,`coin_symbol`,`coin_id`,`chain_id`,`chain_symbol`,`wallet_address`,`hash`,`amount`,`pro_no`,`pro_name`,`admin_no`,`admin_name`,`status`,`created_at`,`updated_at`) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            $transactionInfoData = [
                                $item['transaction_no'],
                                $item['batch_no'],
                                $item['coin_symbol'],
                                $item['coin_id'],
                                $item['chain_id'],
                                $item['chain_symbol'],
                                $item['address'],
                                $reqWallet['data']['hash'],
                                $reqWallet['data']['num'],
                                $item['pro_no'],
                                $item['pro_name'],
                                $item['admin_no'],
                                $item['admin_name'],
                                0,
                                time(),
                                time(),
                            ];
                            $this->getDI()->getShared('db')->query($sql, $transactionInfoData);
                        }

                        var_dump('成功');
                    }


                }


                var_dump('结束');
                //请求钱包通知已完成
                $url = $this->config->wallet_ip.'api/financeWithdrawlOver';
                //请求钱包数据
                $walletOverData['type'] = $type;
                $walletOverData['batch_no'] = $batch_no;
                $walletOverData['transaction_no'] = $transaction_no;
                $walletOverData['pro_no'] = $pro_no;
                var_dump($walletOverData);
                $req = $this->functions->http_request_forWallet($url,'POST',$walletOverData);
                var_dump($req);

            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }


    public function updateMysqlConnect(){
        $key = "wallet-system-admin:mysql:reconnect:TransfertopurseTask";

        //如果存在
        if($this->redis->get($key) == 1){
            return true;
        }else{
            //设置过期时间
            $this->redis->setex($key,60*60*1,1);
            //重连mysql
            $this->dbConnectAgain();
            var_dump('TransfertopurseTask update Mysql connect');
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
