<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ProjectWalletAsset extends BaseBussiness
{
    public function getById($id){
        return $this->getModel('ProjectWalletAsset')->getById($id,$filed = '*');
    }

    public function updateById($id,$data){
        return $this->getModel('ProjectWalletAsset')->updateById($id,$data);
    }

    public function getByAddressAndCoinOld($address,$coin_id){
        return $this->getModel('ProjectWalletAsset')->getByAddressAndCoin($address,$coin_id,$filed='*');
    }

    public function getByAddressAndCoin($address,$coin_type){
        return $this->getModel('ProjectWalletAsset')->getByAddressAndCoin($address,$coin_type,$filed='*');
    }

    public function getByWhere($pro_no,$whereField,$whereData){
        return $this->getModel('ProjectWalletAsset')->getByWhere($pro_no,$whereField,$whereData,$filed='*');
    }


    public function confirmAsset($id){
        $adminSession = $this->session->get('backend');

        $info = $this->getById($id);
        if(!$info){
            $this->result['status'] = -1;
            $this->result['msg'] = '资产记录不存在';
            return $this->result;
        }

        $chain = $this->getModel('SystemChain')->getById($info['chain_id']);
        if(!$chain){
            $this->result['status'] = -1;
            $this->result['msg'] = '公链信息不存在';
            return $this->result;
        }

        $coin = $this->getModel('ProjectCoin')->getById($info['coin_id']);
        if(!$coin){
            $this->result['status'] = -1;
            $this->result['msg'] = '币种信息不存在';
            return $this->result;
        }


        //请求接口确认金额并更新
        if($chain['chain_symbol'] == 'EOS'){
            $update['coin_balance_update'] = $info['coin_balance'];
            $update['chain_fee_balance_update'] = $info['chain_fee_balance'];
            $update['updated_at'] = time();
        }else{
            $url = $this->config->wallet_ip.'api/getAccountMoney';
            $walletData['coin_type'] = $coin['coin_symbol'];
            $walletData['type'] = $chain['chain_symbol'];
            $walletData['address'] = $info['address'];
//        if($coin['coin_type'] == 1){
            //代币
            $walletData['abi'] = $coin['coin_abi'];
            $walletData['contract_address'] = $coin['token_contract'];
//        }

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

            $update['coin_balance_update'] = $reqWallet['data'][$coin['coin_symbol']]['data'];
            $update['chain_fee_balance_update'] = $reqWallet['data'][$coin['chain_symbol']]['data'];
            $update['updated_at'] = time();
        }

        $this->updateById($id,$update);

        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $adminSession['pro_name'];
        $sql['table'] = 'wallet_'.$sql['pro_no'].'_project_wallets_assets';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'update';
        $sql['log_title'] = '更新了id为'.$id.'的资产信息';
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);


        $this->result['status'] = 1;
        $this->result['msg'] = '更新成功';
        return $this->result;

    }

}