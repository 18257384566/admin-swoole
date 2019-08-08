<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class WalletassetController extends ControllerBase
{
    public function getListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //列表页搜索时传来的
        $coin_id = $this->request->get('coin_id');
        $chain_id = $this->request->get('chain_id');
        $batch_no = $this->request->get('batch_no');

        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        $address = $this->request->get('address');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=10;

        $sql=" where 1=1";
        if($chain_id){
            $sql.=" and chain_id =".$chain_id;
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."'";
        }

        if($batch_no){
            $sql.=" and batch_no ='".$batch_no."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_wallets_assets';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select id,address,coin_balance,coin_balance_update from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //计算总和
        $allamount = $this->db->query("select sum(coin_balance) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();


        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'],'chain_id',$chain_id);
        $data['coin_symbol'] = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        $data['address'] = $address;
        $data['coin_id'] = $coin_id;
        $data['chain_id'] = $chain_id;
        $data['batch_no'] = $batch_no;
        $data['allamount']=$allamount['allamount'];
        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        $data["search"]="coin_id=".$coin_id."&chain_id=".$chain_id."&address=".$address."&";

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('walletaddress/asset');
    }


    public function confirmAssetAction(){
        $id = $this->request->get('id');
        $confirm = $this->getBussiness('ProjectWalletAsset')->confirmAsset($id);
        $this->functions->alert($confirm['msg']);
    }

}