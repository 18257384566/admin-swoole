<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ProjectTransaction extends BaseBussiness
{
    public function add($data){
        return $this->getModel('ProjectTransaction')->add($data);
    }

    public function getById($id){
        return $this->getModel('ProjectTransaction')->getById($id,$filed = '*');
    }

    //EOS
    public function transferEOSToPurse($reqData){
        //判断项目是否存在
        $project = $this->getModel('Project')->getDetail($reqData['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //判断事务是否存在
        $transaction = $this->getModel('ProjectTransaction')->getByWheres($reqData['pro_no'],array('transaction_no','coin_symbol'),array($reqData['transaction_no'],$reqData['coin_type']));
        if(!$transaction){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务记录不存在';
            return $this->result;
        }

        //事务详情
        $transactionInfo = $this->getModel('ProjectTransactionInfo')->getByWheresForEos(array('transaction_no','pro_no','coin_symbol'),array($reqData['transaction_no'],$reqData['pro_no'],$reqData['coin_type']));
        if(!$transactionInfo){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务详情不存在';
            return $this->result;
        }


        //获取币种及公链信息
        $coin = $this->getBussiness('ProjectCoin')->getByWhere($reqData['pro_no'],'coin_symbol',$reqData['coin_type']);
        $coin = $coin[0];
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$reqData['type']);

        //更新事务详情表的金额和状态 
        foreach ($transactionInfo as $k=>$item) {
            //修改转出详情内容(无论成功失败)
//            $userWalletAsset = $this->getModel('ProjectWalletAsset')->getByWhere($reqData['pro_no'], 'batch_no', $item['batch_no']);
//            foreach ($userWalletAsset as $key => $value) {
//                if ($item['wallet_address'] == $value['address']) {
//                    $sql = "UPDATE wallet_" . $reqData['pro_no'] . "_project_transaction_info SET `amount` = " . $value['coin_balance'] . ",wallet_address='" . $value['address'] . "',updated_at=" . time() . " WHERE id=" . $item['id'];
//                    $this->db->query($sql);
//                } elseif ($k == $key) {
//                    $sql = "UPDATE wallet_" . $reqData['pro_no'] . "_project_transaction_info SET amount = " . $value['coin_balance'] . ",wallet_address='" . $value['address'] . "',updated_at=" . time() . " WHERE id=" . $item['id'];
//                    $this->db->query($sql);
//                }
//            }

            if ($reqData['status'] == 2) {
                $transactionInfoData['status'] = -1;
            } else {
                //成功
                $transactionInfoData['status'] = 1;
            }
            //修改事务详情状态
            $this->getBussiness('ProjectTransactionInfo')->updateByWhere($transactionInfoData,'id',$item['id']);
        }


        //更新事务表
        if($reqData['status'] == 1){
            //将转出历史成功数量为全部数量
            $sql = "UPDATE wallet_".$reqData['pro_no']."_project_transaction SET success = `count`,updated_at=".time()." WHERE transaction_no='".$reqData['transaction_no']."' and coin_id='".$coin['id']."'";
            $this->db->query($sql);

            //扣用户钱包资产
            $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET coin_balance = 0,updated_at=".time()." WHERE batch_no='".$reqData['batch_no']."' and coin_symbol='".$coin['coin_symbol']."' and pro_no='".$reqData['pro_no']."'";
            $this->db->query($sqlAsset);

            //扣出账钱包余额及手续费
            $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$reqData['num'].",chain_fee_balance = `chain_fee_balance`-".$reqData['num'].",updated_at=".time()." WHERE address='".$reqData['from_address']."' and coin_id='".$coin['id']."' and pro_no='".$reqData['pro_no']."'";
            $this->db->query($sqlAsset);

        }else{
            //失败
            $sql = "UPDATE wallet_" . $reqData['pro_no'] . "_project_transaction SET fail = `count`,success = 0,updated_at=" . time() . " WHERE transaction_no='" . $reqData['transaction_no'] . "' and coin_id='" . $coin['id'] . "'";
            $this->db->query($sql);
        }


        //转到冷钱包记录流水
        $flowData = [
            'pro_no'=>$reqData['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$reqData['from_address'],
            'to_address'=>$reqData['leng'],
            'chain_id'=>$chain['id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'hash'=>$reqData['hash'],
            'coin_chain_amount'=>$reqData['fee'],
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$reqData['num'],
            'flow_type'=>4,//1出账钱包充值，2 手续费钱包充值，3：手续费钱包转出4：转入冷钱包
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('ProjectServerWalletsFlow')->add($flowData);

        $this->result['status'] = 1;
        $this->result['msg'] = '转出成功';
        return $this->result;


    }

    //转出冷钱包之后钱包调用后的操作
    public function transferToPurse($reqData){
        //判断项目是否存在
        $project = $this->getModel('Project')->getDetail($reqData['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //判断事务是否存在
        $transaction = $this->getModel('ProjectTransaction')->getByWheres($reqData['pro_no'],array('transaction_no','coin_symbol'),array($reqData['transaction_no'],$reqData['coin_type']));
        if(!$transaction){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务记录不存在';
            return $this->result;
        }

        //事务详情
        $transactionInfo = $this->getModel('ProjectTransactionInfo')->getByWheres(array('transaction_no','wallet_address','coin_symbol'),array($reqData['transaction_no'],$reqData['address'],$reqData['coin_type']));
        if(!$transactionInfo){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务详情不存在';
            return $this->result;
        }


        //获取币种及公链信息
        $coin = $this->getBussiness('ProjectCoin')->getByWhere($reqData['pro_no'],'coin_symbol',$reqData['coin_type']);
        $coin = $coin[0];
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$reqData['type']);

        //更新事务表
        //0处理中，1完成，-1失败，2重转完成
        if($reqData['status'] == 2){
            //判断原本状态 再更改事务
            if($transactionInfo['status'] == 0){
                //通知失败
                $sql = "UPDATE wallet_".$reqData['pro_no']."_project_transaction SET fail = `fail`+1,success = `success`-1,updated_at=".time()." WHERE transaction_no='".$reqData['transaction_no']."' and coin_id='".$coin['id']."'";
                $this->db->query($sql);

            }

        }else{
            //扣用户钱包资产
            //判断资产与num的差额是否为0
            $allamount = $this->db->query("select coin_balance as coin_balance from wallet_".$reqData['pro_no']."_project_wallets_assets WHERE address='".$reqData['address']."' and coin_id='".$coin['id']."' and pro_no='".$reqData['pro_no']."'");
            $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $allamount = $allamount->fetch();
            if(($allamount['coin_balance'] - $reqData['num']) < 0){
                $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET coin_balance = 0,updated_at=".time()." WHERE address='".$reqData['address']."' and coin_id='".$coin['id']."' and pro_no='".$reqData['pro_no']."'";
                $this->db->query($sqlAsset);
            }else{
                $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$reqData['num'].",updated_at=".time()." WHERE address='".$reqData['address']."' and coin_symbol='".$coin['coin_symbol']."' and pro_no='".$reqData['pro_no']."'";
                $this->db->query($sqlAsset);
            }

            $sql = "UPDATE wallet_".$reqData['pro_no']."_project_transaction SET success = `success`+1,updated_at=".time()." WHERE transaction_no='".$reqData['transaction_no']."' and coin_id='".$coin['id']."'";
            $this->db->query($sql);
        }

        //更新公链手续费--手续费字段及公链余额
        $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`-".$reqData['fee'].",updated_at=".time()." WHERE address='".$reqData['address']."' and chain_symbol='".$chain['chain_symbol']."' and pro_no='".$reqData['pro_no']."'";
        $this->db->query($sqlAsset);
        $sqlAsset = "UPDATE wallet_".$reqData['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$reqData['fee'].",updated_at=".time()." WHERE address='".$reqData['address']."' and coin_symbol='".$chain['chain_symbol']."' and pro_no='".$reqData['pro_no']."'";
        $this->db->query($sqlAsset);

        //更新事务详情表（根据hash）
        $transactionInfoData = [
            'hash'=>$reqData['hash'],
            'fee'=>$reqData['fee'],
            'amount'=>$reqData['num'],
        ];

        if($reqData['status'] == 2){
            //失败
            $transactionInfoData['status'] = -1;
        }else{
            //成功
            $transactionInfoData['status'] = 1;
        }

        $this->getBussiness('ProjectTransactionInfo')->updateByWhere($transactionInfoData,'id',$transactionInfo['id']);

        //转到冷钱包记录流水
        $flowData = [
            'pro_no'=>$reqData['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$reqData['from_address'],
            'to_address'=>$reqData['leng'],
            'chain_id'=>$chain['id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'hash'=>$reqData['hash'],
            'coin_chain_amount'=>$reqData['fee'],
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$reqData['num'],
            'flow_type'=>4,//1出账钱包充值，2 手续费钱包充值，3：手续费钱包转出4：转入冷钱包
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('ProjectServerWalletsFlow')->add($flowData);

        $this->result['status'] = 1;
        $this->result['msg'] = '转出成功';
        return $this->result;

    }


    public function finishTransferToPurse($reqData){
        //判断项目是否存在
        $project = $this->getModel('Project')->getDetail($reqData['pro_no'],'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //判断事务是否存在
        $transaction = $this->getModel('ProjectTransaction')->getByWhere($reqData['pro_no'],'transaction_no',$reqData['transaction_no']);
        if(!$transaction){
            $this->result['status'] = -1;
            $this->result['msg'] = '事务记录不存在';
            return $this->result;
        }

        //计算事务里的未完成数量
        foreach ($transaction as $value) {
            $incomplete = $this->db->query("select sum(incomplete) as incomplete from wallet_".$reqData['pro_no']."_project_transaction where transaction_no = '" . $value['transaction_no'] . "' and batch_no='" . $value['batch_no'] . "'");
            $incomplete->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $incomplete = $incomplete->fetch();

            if($incomplete['incomplete'] > 0){
                $this->result['status'] = -1;
                $this->result['msg'] = '事务未全部完成';
                return $this->result;
            }

        }

        //事务详情
//        $transactionInfo = $this->getModel('ProjectTransactionInfo')->getByWheresForEos(array('transaction_no','pro_no','coin_symbol'),array($reqData['transaction_no'],$reqData['pro_no'],'EOS'));
//        if(!$transactionInfo){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '事务详情不存在';
//            return $this->result;
//        }

        $sql = "UPDATE wallet_".$reqData['pro_no']."_project_transaction SET status=1,updated_at=" . time() . " WHERE transaction_no='" . $reqData['transaction_no'] . "' and pro_no='" . $reqData['pro_no'] . "'";
        $this->db->query($sql);

//        if($transaction[0]['chain_symbol'] == 'EOS'){
//            foreach ($transactionInfo as $k=>$item) {
//                //修改转出详情内容
//                $userWalletAsset = $this->getModel('ProjectWalletAsset')->getByWhere($reqData['pro_no'], 'batch_no', $item['batch_no']);
//                foreach ($userWalletAsset as $key => $v) {
//                    if ($item['wallet_address'] == $v['address']) {
//                        $sql = "UPDATE wallet_" . $reqData['pro_no'] . "_project_transaction_info SET `amount` = " . $v['coin_balance'] . ",wallet_address='" . $v['address'] . "',updated_at=" . time() . " WHERE id=" . $item['id'];
//                        $this->db->query($sql);
//                    } elseif ($k == $key) {
//                        $sql = "UPDATE wallet_" . $reqData['pro_no'] . "_project_transaction_info SET `amount` = " . $v['coin_balance'] . ",wallet_address='" . $v['address'] . "',updated_at=" . time() . " WHERE id=" . $item['id'];
//                        $this->db->query($sql);
//                    }
//                }
//            }
//        }



        $this->result['status'] = 1;
        $this->result['msg'] = '通知成功';
        return $this->result;
    }
}