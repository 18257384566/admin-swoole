<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class WallettypeController extends ControllerBase
{
    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'wallet_address',
            'show_name'=>'钱包地址管理',
            'top_id'=>0,
        ];

        return $this->getBussiness('Permission')->add($data);

    }

    public function getListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'wallet_type',
            'show_name'=>'钱包类型管理',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'wallet_type_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'wallet_type_do',
            'show_name'=>'可操作',
            'top_id'=>$second_top_id,
        ];
        $do_id = $this->getBussiness('Permission')->add($datas);

        $adminPermission = $this->getBussiness('Permission')->getPermission($data['name']);


        //将权限传人视图
        $data['is_super'] = $adminPermission['is_super'];$data['is_power'] = $adminPermission['is_power'];
        $data['permission'] = $adminPermission['permissions'];
        $data['do'] = $do_id;


        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $chain_id = $this->request->get('chain_id');
        $admin_no = $this->request->get('admin_no');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=10;

        $sql=" where 1=1";
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }

        if($chain_id){
            $sql.=" and chain_id =".$chain_id;
        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_coin';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select id,chain_id,chain_symbol,coin_symbol,admin_no,admin_name,status,created_at from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //所有币种
        $data['chain'] = $this->getBussiness('SystemChain')->getAll();

        //所有管理员
        $admin = $this->db->query("select admin_no,admin_name,id,created_at from wallet_system_project_admin where pro_no = '".$adminSession['pro_no']."'".$orderBy);
        $admin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $data['admin'] = $admin->fetchAll();


        $data['chain_id'] = $chain_id;
        $data['admin_no'] = $admin_no;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="chain_id=".$chain_id."&admin_no=".$admin_no."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="chain_id=".$chain_id."&admin_no=".$admin_no."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('wallettype/list');
    }

    public function addViewAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        $this->view->pick('wallettype/add');
    }

    public function addAction(){
        $data['token_contract'] = $this->request->getPost('token_contract');
        $data['coin_abi'] = $this->request->getPost('coin_abi');
        $data['transfer_min'] = $this->request->getPost('transfer_min');
        //校验数据
        $validation = $this->paValidation;
        $validation->addWallettype();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }
        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        $add = $this->getBussiness('ProjectCoin')->add($data);
        if($add['status'] != 1){
            $this->functions->alert($add['msg']);
        }
        $this->functions->alert($add['msg'],'/'.$pro_no.'/wallettype/list');
    }

    public function infoViewAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        $id = $this->request->get('id');
        $info = $this->getBussiness('ProjectCoin')->getById($id);

        if(!$info){
            $this->funstions->alert('此记录不存在');
        }
        $this->view->info = $info;
        $this->view->pick('wallettype/detail');
    }

    public function infoAction(){
        $id = $this->request->get('id');
        $data['transfer_min'] = $this->request->getPost('transfer_min');
        //校验数据
        $validation = $this->paValidation;
        $validation->editWallettype();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $info = $this->getBussiness('ProjectCoin')->info($id,$data);
//        if($info['status'] != 1){
//            $this->functions->alert($info['msg']);
//        }
        $this->functions->alert($info['msg']);
    }

    public function updateStatusAction(){
        $id = $this->request->get('id');
        $data['status'] = $this->request->get('status');
        $update = $this->getBussiness('ProjectCoin')->updateStatus($id,$data);
        if($update['status']!=1){
            $this->functions->alert($update['msg']);
        }
        $this->functions->alert($update['msg']);
    }



}