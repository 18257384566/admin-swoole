<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ProjectCoin extends BaseBussiness
{
    public function getAll($pro_no){
        return $this->getModel('ProjectCoin')->getAll($pro_no,$field='*');
    }

    public function getByWhere($pro_no,$whereFiled,$whereData,$filed='*'){
        return $this->getModel('ProjectCoin')->getByWhere($pro_no,$whereFiled,$whereData,$filed);
    }

    public function getById($id){
        return $this->getModel('ProjectCoin')->getById($id,$filed = '*');
    }


    public function updateById($id,$data){
        return $this->getModel('ProjectCoin')->updateById($id,$data);
    }


    public function add($reqData){
        $adminSession = $this->session->get('backend');

        $coin_token_contract = $this->getByWhere($adminSession['pro_no'],'token_contract',$reqData['token_contract']);
        if($coin_token_contract){
            $this->result['status'] = -1;
            $this->result['msg'] = '钱包类型已存在';
            return $this->result;
        }

//        $coin_abi = $this->getByWhere($adminSession['pro_no'],'coin_abi',$reqData['coin_abi']);
//        if($coin_abi){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '钱包类型已存在';
//            return $this->result;
//        }


        //请求钱包验证代币
        $url = $this->config->wallet_ip.'api/validationCoin';
        //公链名
        $walletData['type'] = 'ETH';
        $walletData['contract_address'] = $reqData['token_contract'];
        $walletData['abi'] = $reqData['coin_abi'];
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


        //数据处理
        $add['pro_no'] = $adminSession['pro_no'];
        $project = $this->getBussiness('Project')->getDetail($add['pro_no']);
        $add['pro_name'] = $project['pro_name'];
        $add['admin_no'] = $adminSession['admin_no'];
        $add['admin_name'] = $adminSession['account'];
        $add['created_at'] = $add['updated_at'] = time();
        $add['chain_symbol'] = $reqWallet['data']['type'];
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$reqWallet['data']['type']);
        $add['chain_id'] = $chain['id'];
        $add['coin_name'] = $reqWallet['data']['name'];
        $add['coin_symbol'] = $reqWallet['data']['coin_name'];
        $add['token_contract'] = $reqWallet['data']['contract_address'];
        $add['coin_abi'] = $reqWallet['data']['abi'];
        $add['coin_type'] = 1;
        $add['transfer_min'] = $reqData['transfer_min'];

        $id = $this->getModel('ProjectCoin')->add($add);

        //redis
        $redisData = [
            $add['coin_symbol']=>[
                'contractAddress'=>$add['token_contract'],
                'abi'=>$add['coin_abi'],
                'decimals'=>$reqWallet['data']['decimals'],
            ]
        ];
        $this->getBussiness('RedisCache')->addCoin($redisData);


        //生成代币后获取出账钱包公链资产，新增代币资产记录
        $wallet = $this->getModel('ProjectWallet')->getByChainAndType($adminSession['pro_no'],$chain['id'],$type=2,$filed='*');
        if($wallet){
            $chainAsset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($wallet['address'],$reqWallet['data']['type']);
            if($chainAsset){
                $assetData = [
                    'pro_no'=>$adminSession['pro_no'],
                    'pro_name'=>$project['pro_name'],
                    'address'=>$wallet['address'],
                    'chain_symbol'=>$reqWallet['data']['type'],
                    'chain_id'=>$chain['id'],
                    'chain_fee_balance'=>$chainAsset['coin_balance'],
                    'coin_symbol'=>$reqWallet['data']['coin_name'],
                    'coin_id'=>$id,
                    'coin_balance'=>0,
                    'created_at'=>time(),
                    'updated_at'=>time(),
                    'batch_no'=>'',
                ];

            }else{
                $assetData = [
                    'pro_no'=>$adminSession['pro_no'],
                    'pro_name'=>$project['pro_name'],
                    'address'=>$wallet['address'],
                    'chain_symbol'=>$reqWallet['data']['type'],
                    'chain_id'=>$chain['id'],
                    'chain_fee_balance'=>0,
                    'coin_symbol'=>$reqWallet['data']['coin_name'],
                    'coin_id'=>$id,
                    'coin_balance'=>0,
                    'created_at'=>time(),
                    'updated_at'=>time(),
                    'batch_no'=>'',
                ];
            }
            $this->getModel('ProjectWalletsAssets')->add($assetData);
        }


        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $project['pro_name'];
        $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_coin';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'insert';
        $sql['log_title'] = '添加钱包类型'.$add['coin_symbol'];
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);



        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        return $this->result;

    }

    public function updateStatus($id,$data){
        $adminSession = $this->session->get('backend');

        $coin = $this->getById($id);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '记录不存在';
            return $this->result;
        }

        $data['updated_at'] = time();
        $this->updateById($id,$data);

        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $adminSession['pro_name'];
        $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_coin';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'update';
        if($data['status'] == 1){
            $sql['log_title'] = '启用钱包类型'.$coin['coin_symbol'];
        }else{
            $sql['log_title'] = '禁用钱包类型'.$coin['coin_symbol'];
        }
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);


        $this->result['status'] = 1;
        $this->result['msg'] = '修改成功';
        return $this->result;
    }

    public function info($id,$data){
        $adminSession = $this->session->get('backend');

        $coin = $this->getById($id);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '记录不存在';
            return $this->result;
        }

        //公链币不小于最小值
        if($coin['coin_symbol'] == $coin['chain_symbol']){
            $transfer_min = $this->config->transfer_min[$coin['chain_symbol']];
            if($data['transfer_min'] < $transfer_min){
                $this->result['status'] = -1;
                $this->result['msg'] = '最小值不得低于'.$transfer_min;
                return $this->result;
            }
        }



        $data['updated_at'] = time();
        $this->updateById($id,$data);

        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $adminSession['pro_name'];
        $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_coin';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'update';
        $sql['log_title'] = '修改了'.$coin['coin_symbol'].'的钱包转入最小值';
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);


        $this->result['status'] = 1;
        $this->result['msg'] = '修改成功';
        return $this->result;
    }

}