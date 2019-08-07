<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class Deposit extends BaseBussiness
{
    public function dealOrder($data){
        //获取币种及公链信息
        $pro_no = $data['pro_no'];

        //获取项目信息
        $project = $this->getModel('Project')->getDetail($pro_no,'pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $coin = $this->getBussiness('ProjectCoin')->getByWhere($pro_no,'coin_symbol',$data['coin_type']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种不存在';
            return $this->result;
        }

        $coin = $coin[0];
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$data['type']);
        $project = $this->getBussiness('Project')->getDetail($pro_no);


        if($data['wallet_type'] == 'user'){
            $Wallet = $this->db->query("select * from wallet_".$pro_no."_project_user_wallets where  address = '".$data['address']."' and chain_symbol='".$data['type']."'");
            $Wallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $Wallet = $Wallet->fetch();
            if(!$Wallet){
                $this->result['status'] = -1;
                $this->result['msg'] = '用户钱包地址不存在';
                return $this->result;
            }

            //查询hash是否存在
            $hash = $this->db->query("select * from wallet_".$pro_no."_project_wallets_flow where hash='".$data['hash']."' and flow_type=1 and coin_symbol='".$data['coin_type']."' and to_address='".$data['address']."' and pro_no='".$project['pro_no']."' ");
            $hash->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $hash = $hash->fetch();
            if($hash){
                $this->result['status'] = -1;
                $this->result['msg'] = 'hash已存在';
                return $this->result;
            }

        }else{
            $Wallet = $this->db->query("select * from wallet_".$pro_no."_project_wallets where  address = '".$data['address']."' and chain_symbol='".$data['type']."'");
            $Wallet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $Wallet = $Wallet->fetch();
            if(!$Wallet){
                $this->result['status'] = -1;
                $this->result['msg'] = '项目钱包地址不存在';
                return $this->result;
            }

            //查询hash是否存在
            $hash = $this->db->query("select * from wallet_".$pro_no."_project_server_wallets_flow where hash='".$data['hash']."' and coin_symbol='".$data['coin_type']."' and to_address='".$data['address']."' and pro_no='".$project['pro_no']."' ");
            $hash->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $hash = $hash->fetch();
            if($hash){
                $this->result['status'] = -1;
                $this->result['msg'] = 'hash已存在';
                return $this->result;
            }
        }


        if($data['type'] == $data['coin_type']){
            //公链
            //更新资产
            $asset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($data['address'],$data['coin_type']);
            if(!$asset){
                $assetData = [
                    'pro_no'=>$pro_no,
                    'pro_name'=>$project['pro_name'],
                    'address'=>$data['address'],
                    'chain_symbol'=>$data['type'],
                    'chain_id'=>$chain['id'],
                    'chain_fee_balance'=>0,
                    'coin_symbol'=>$data['coin_type'],
                    'coin_id'=>$coin['id'],
                    'coin_balance'=>0,
                    'created_at'=>time(),
                    'updated_at'=>time(),
                ];

                if($data['wallet_type'] == 'user'){
                    $assetData['batch_no'] = $Wallet['batch_no'];
                }else{
                    $assetData['batch_no'] = '';
                }
                $this->getModel('ProjectWalletsAssets')->add($assetData);
            }

            //新增后更新钱包手续费资产
            $sqlAsset = "UPDATE wallet_".$pro_no."_project_wallets_assets SET coin_balance = `coin_balance`+".$data['num'].",updated_at=".time()." WHERE address='".$data['address']."' and coin_symbol='".$data['type']."' and pro_no='".$pro_no."'";
            $this->db->query($sqlAsset);
            $sqlChainAsset = "UPDATE wallet_".$pro_no."_project_wallets_assets SET chain_fee_balance = `chain_fee_balance`+".$data['num'].",updated_at=".time()." WHERE address='".$data['address']."' and chain_symbol='".$data['type']."' and pro_no='".$pro_no."'";
            $this->db->query($sqlChainAsset);

            //记录日志
//            $sql['pro_no'] = $pro_no;
//            $sql['pro_name'] = $project['pro_name'];
//            $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_wallets_assets';
//            $sql['ip'] = $_SERVER['REMOTE_ADDR'];
//            $sql['admin_no'] = '';
//            $sql['admin_name'] = '';
//            $sql['created_at'] = $sql['updated_at'] = time();
//            $sql['sql_type'] = 'update';
//            $sql['log_title'] = '充值公链';
//            $sql['sql'] = $sqlAsset.','.$sqlChainAsset;
//            $this->getModel('AdminLog')->add($sql);


        }else{
            //更新资产
            $asset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($data['address'],$data['coin_type']);
            if(!$asset){
                $assetData = [
                    'pro_no'=>$pro_no,
                    'pro_name'=>$project['pro_name'],
                    'address'=>$data['address'],
                    'chain_symbol'=>$data['type'],
                    'chain_id'=>$chain['id'],
                    'chain_fee_balance'=>0,
                    'coin_symbol'=>$data['coin_type'],
                    'coin_id'=>$coin['id'],
                    'coin_balance'=>$data['num'],
                    'created_at'=>time(),
                    'updated_at'=>time(),
                ];

                if($data['wallet_type'] == 'user'){
                    $assetData['batch_no'] = $Wallet['batch_no'];
                }else{
                    $assetData['batch_no'] = '';
                }
                $this->getModel('ProjectWalletsAssets')->add($assetData);

            }else{
                $update['coin_balance'] = $asset['coin_balance']+$data['num'];
                $update['updated_at'] = time();
                $this->getModel('ProjectWalletAsset')->updateById($asset['id'],$update);

            }

        }

        //流水
        //充值订单编号
//        $deposit_no = $this->createWithOrderNum($coin['coin_symbol']);
        if($data['wallet_type'] != 'user'){
            if($data['wallet_type'] == 'transaction'){
                //出账钱包
                //记录流水
                $flowData = [
                    'pro_no'=>$project['pro_no'],
                    'pro_name'=>$project['pro_name'],
                    'from_address'=>$data['from_address'],
                    'to_address'=>$data['address'],
                    'chain_id'=>$coin['chain_id'],
                    'chain_symbol'=>$coin['chain_symbol'],
                    'hash'=>$data['hash'],
                    'coin_chain_amount'=>$data['fee'],
                    'coin_id'=>$coin['id'],
                    'coin_symbol'=>$coin['coin_symbol'],
                    'coin_amount'=>$data['num'],
                    'flow_type'=>1,//入账
                    'created_at'=>time(),
                    'updated_at'=>time(),
                ];

            }elseif($data['wallet_type'] == 'fee'){
                //手续费钱包
                $flowData = [
                    'pro_no'=>$project['pro_no'],
                    'pro_name'=>$project['pro_name'],
                    'from_address'=>$data['from_address'],
                    'to_address'=>$data['address'],
                    'chain_id'=>$coin['chain_id'],
                    'chain_symbol'=>$coin['chain_symbol'],
                    'hash'=>$data['hash'],
                    'coin_chain_amount'=>$data['fee'],
                    'coin_id'=>$coin['id'],
                    'coin_symbol'=>$coin['coin_symbol'],
                    'coin_amount'=>$data['num'],
                    'flow_type'=>2,//入账
                    'created_at'=>time(),
                    'updated_at'=>time(),
                ];
            }
            $this->getModel('ProjectServerWalletsFlow')->add($flowData);
        }elseif($data['wallet_type'] == 'user'){
            //记录充值订单
//            $depositData['pro_no'] = $project['pro_no'];
//            $depositData['pro_name'] = $project['pro_name'];
//            $depositData['deposit_no'] = $deposit_no;
//            $depositData['address'] = $data['address'];
//            $depositData['chain_symbol'] = $coin['chain_symbol'];
//            $depositData['chain_id'] = $coin['chain_id'];
//            $depositData['coin_symbol'] = $coin['coin_symbol'];
//            $depositData['coin_id'] = $coin['coin_id'];
//            $depositData['coin_amount'] = $data['num'];
//            $depositData['created_at'] = $depositData['updated_at'] = time();
//            $this->getBussiness('Deposit')->add($depositData);

            //记录流水
            $flowData = [
                'pro_no'=>$project['pro_no'],
                'pro_name'=>$project['pro_name'],
                'from_address'=>$data['from_address'],
                'to_address'=>$data['address'],
                'chain_id'=>$coin['chain_id'],
                'chain_symbol'=>$coin['chain_symbol'],
                'hash'=>$data['hash'],
                'coin_chain_amount'=>$data['fee'],
                'coin_id'=>$coin['id'],
                'coin_symbol'=>$coin['coin_symbol'],
                'coin_amount'=>$data['num'],
                'obj_name'=>'',//表名
                'obj_id'=>0,
                'flow_type'=>1,//入账
                'created_at'=>time(),
                'updated_at'=>time(),
            ];
            $this->getModel('ProjectWalletsFlow')->add($flowData);
        }

        //如果充值方是用户钱包再通知
        if($data['wallet_type'] == 'user'){
            //通知项目方
            $noticeData = [
                'send_type'=>'transaction',//充值
                'status'=>$data['status'],
                'from_address'=>$data['from_address'],
                'to_address'=>$data['address'],
                'num'=>$data['num'],
                'hash'=>$data['hash'],
                'fee'=>$data['fee'] ? $data['fee'] : 0,
                'coin_type'=>$data['coin_type'],
                'pro_no'=>$pro_no,
//                'order_no'=>$deposit_no,
                'order_no'=>'',
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

    //生成订单编号
    private function createWithOrderNum($coin_name){
        $time = time();
        $uniqueString = $this->functions->uniqueString(8);
        return "MolecularWallet_".$coin_name.$time.$uniqueString;

    }

    //手续费转出
    public function addFee($data){
        //获取项目信息
        $project = $this->getBussiness('Project')->getDetail($data['pro_no']);
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        //获取币种及公链信息
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$data['type']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }

        //币种也是公链（ETH，BTC）
        $coin = $this->getBussiness('ProjectCoin')->getByWhere($data['pro_no'],'coin_symbol',$data['type']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种信息不存在';
            return $this->result;
        }

        $coin = $coin[0];


        //更新资产
        $asset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($data['from_address'],$data['type']);
        if(!$asset){
            $this->result['status'] = -1;
            $this->result['msg'] = '手续费资产记录不存在';
            return $this->result;
        }

        $update['coin_balance'] = $asset['coin_balance']-$data['num'] ;
        $update['updated_at'] = time();
        $this->getModel('ProjectWalletAsset')->updateById($asset['id'],$update);



        //手续费钱包出账流水
        $flowData = [
            'pro_no'=>$project['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$data['from_address'],
            'to_address'=>$data['to_address'],
            'chain_id'=>$chain['id'],
            'chain_symbol'=>$chain['chain_symbol'],
            'hash'=>$data['hash'],
            'coin_chain_amount'=>$data['fee'],
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$data['num'],
            'flow_type'=>3,
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('ProjectServerWalletsFlow')->add($flowData);


        $this->result['status'] = 1;
        $this->result['msg'] = '成功';
        return $this->result;
    }


    public function add($data){
        $this->getModel('ProjectDeposit')->add($data);
    }
}