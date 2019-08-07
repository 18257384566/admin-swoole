<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ResourceManage extends BaseBussiness
{
    public function buyRam($reqData){
        $adminSession = $this->getBussiness('Permission')->getSession();

        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':eos:ResourceManage:ram',20);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请勿重复操作';
            return $this->result;
        }
        
        //获取项目信息
        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'pro_no,pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $coin = $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'],'coin_symbol',$reqData['chain_symbol']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种不存在';
            return $this->result;
        }

        $coin = $coin[0];
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$reqData['chain_symbol']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }

        //判断出账钱包是否存在
        $transactionWallet = $this->getModel('ProjectWallet')->getProWallet($adminSession['pro_no'],'EOS',2,$filed='*');
        if(!$transactionWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '请先设置出账钱包';
            return $this->result;
        }

        //判断密码正确性
        if(md5($reqData['password']) != $transactionWallet['password']){
            $this->result['status'] = -1;
            $this->result['msg'] = '密码错误,请重新输入';
            return $this->result;
        }


        //购买
        $url = $this->config->wallet_ip.'api/buyRam';
        $walletData['type'] = $reqData['chain_symbol'];
        $walletData['account_name'] = $transactionWallet['address'];
        $walletData['password'] = $reqData['password'];
        $walletData['num'] = $reqData['ram_num'];

        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
//        var_dump($reqWallet);

        if (!isset($reqWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '请求失败';
            return $this->result;
        }

        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }

        //扣出账钱包资产
        $sqlAsset = "UPDATE wallet_".$project['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$reqData['ram_num'].",chain_fee_balance = `chain_fee_balance`-".$reqData['ram_num'].",updated_at=".time()." WHERE address='".$transactionWallet['address']."' and coin_id='".$coin['id']."' and pro_no='".$project['pro_no']."'";
        $this->db->query($sqlAsset);


        //新增系统资源购买流水
        $flowData = [
            'pro_no'=>$project['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$transactionWallet['address'],
            'to_address'=>$transactionWallet['address'],
            'chain_id'=>$coin['chain_id'],
            'chain_symbol'=>$coin['chain_symbol'],
            'hash'=>$reqWallet['data'],
            'coin_chain_amount'=>'0',
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$reqData['ram_num'],
            'flow_type'=>5,//购买资源
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('ProjectServerWalletsFlow')->add($flowData);

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_server_wallets_flow';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'insert';
        $sqlData['log_title'] = '购买EOS-RAM资源';
        $sqlData['sql'] = '';
        $this->getModel('AdminLog')->add($sqlData);

        $this->result['status'] = 1;
        $this->result['msg'] = '购买成功';
        return $this->result;

    }

    public function buyCpuNet($reqData){
        $adminSession = $this->getBussiness('Permission')->getSession();

        //不可重复操作
        $redisLock = $this->getBussiness('RedisCache')->redisLock($adminSession['pro_no'].':'.$adminSession['account'].':eos:ResourceManage:cpunet',20);
        if(!$redisLock){
            $this->result['status'] = -1;
            $this->result['msg'] = '请勿重复操作';
            return $this->result;
        }


        //获取项目信息
        $project = $this->getModel('Project')->getDetail($adminSession['pro_no'],'pro_no,pro_name');
        if(!$project){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目信息不存在';
            return $this->result;
        }

        $coin = $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'],'coin_symbol',$reqData['chain_symbol']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种不存在';
            return $this->result;
        }

        $coin = $coin[0];
        $chain = $this->getBussiness('SystemChain')->getByWhere('chain_symbol',$reqData['chain_symbol']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }

        //判断出账钱包是否存在
        $transactionWallet = $this->getModel('ProjectWallet')->getProWallet($adminSession['pro_no'],'EOS',2,$filed='*');
        if(!$transactionWallet){
            $this->result['status'] = -1;
            $this->result['msg'] = '请先设置出账钱包';
            return $this->result;
        }

        //判断密码正确性
        if(md5($reqData['password']) != $transactionWallet['password']){
            $this->result['status'] = -1;
            $this->result['msg'] = '密码错误,请重新输入';
            return $this->result;
        }


//        dd($reqData);

        //购买
        $url = $this->config->wallet_ip.'api/buyCpuNet';
        $walletData['type'] = $reqData['chain_symbol'];
        $walletData['account_name'] = $transactionWallet['address'];
        $walletData['password'] = $reqData['password'];
        $walletData['cpu_num'] = $reqData['cpu_num'];
        $walletData['net_num'] = $reqData['net_num'];

        $reqWallet = $this->functions->http_request_forWallet($url,'POST',$walletData);
//        var_dump($reqWallet);
        if (!isset($reqWallet)) {
            $this->result['status'] = -1;
            $this->result['msg'] = '请求失败';
            return $this->result;
        }

        if($reqWallet['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $reqWallet['msg'];
            return $this->result;
        }

        //扣出账钱包资产
        $num = $reqData['cpu_num'] + $reqData['net_num'];
        $sqlAsset = "UPDATE wallet_".$project['pro_no']."_project_wallets_assets SET coin_balance = `coin_balance`-".$num.",chain_fee_balance = `chain_fee_balance`-".$num.",updated_at=".time()." WHERE address='".$transactionWallet['address']."' and coin_id='".$coin['id']."' and pro_no='".$project['pro_no']."'";
        $this->db->query($sqlAsset);


        //新增系统资源购买流水
        $flowData = [
            'pro_no'=>$project['pro_no'],
            'pro_name'=>$project['pro_name'],
            'from_address'=>$transactionWallet['address'],
            'to_address'=>$transactionWallet['address'],
            'chain_id'=>$coin['chain_id'],
            'chain_symbol'=>$coin['chain_symbol'],
            'hash'=>$reqWallet['data'],
            'coin_chain_amount'=>'0',
            'coin_id'=>$coin['id'],
            'coin_symbol'=>$coin['coin_symbol'],
            'coin_amount'=>$reqData['cpu_num']+$reqData['net_num'],
            'flow_type'=>5,//购买资源
            'created_at'=>time(),
            'updated_at'=>time(),
        ];
        $this->getModel('ProjectServerWalletsFlow')->add($flowData);

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_server_wallets_flow';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'insert';
        $sqlData['log_title'] = '购买EOS-CPU/NET资源';
        $sqlData['sql'] = '';
        $this->getModel('AdminLog')->add($sqlData);

        $this->result['status'] = 1;
        $this->result['msg'] = '购买成功';
        return $this->result;

    }

}