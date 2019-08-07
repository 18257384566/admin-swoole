<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class ProjectController extends ControllerBase
{
    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'project',
            'show_name'=>'项目详情',
            'top_id'=>0,
        ];

        return $this->getBussiness('Permission')->add($data);

    }

    public function detailAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'project_detail',
            'show_name'=>'项目详情',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'project_detail_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'project_detail_do',
            'show_name'=>'可操作',
            'top_id'=>$second_top_id,
        ];
        $do_id = $this->getBussiness('Permission')->add($datas);

        $adminPermission = $this->getBussiness('Permission')->getPermission($data['name']);



        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        //项目详情
        $pro_no = $adminSession['pro_no'];
        $project = $this->getBussiness('Project')->getDetail($pro_no);
        $this->view->pro_name = $project['pro_name'];
        $this->view->contacts_name = $project['contacts_name'];
        $this->view->contacts_phone = $project['contacts_phone'];
        $this->view->corporate_name = $project['corporate_name'];
        $this->view->secret_key = $project['secret_key'];
        $this->view->security_url = $project['security_url'];
        $this->view->project_wallet_url = $project['project_wallet_url'];
        $this->view->project_hash_url = $project['project_hash_url'];
        $this->view->login_url = $project['login_url'];
        $this->view->encryption_security_url = $project['encryption_security_url'];

        //将权限传人视图
        $permission['is_super'] = $adminPermission['is_super'];
        $permission['permission'] = $adminPermission['permissions'];
        $permission['do'] = $do_id;
        $this->view->permission = $permission;

        //冷钱包
        $lengWallet = $this->getBussiness('ProjectWallet')->getList($pro_no,$type=1);
        $this->view->lengWallet = $lengWallet;
        $i = 0;
        foreach ($lengWallet as $item) {
            $lengWalletIds[$i] = $item['chain_id'];
            $i = $i+1;
        }

        $chains = $this->getChains($lengWalletIds,1);
        $this->view->chains = $chains;

        //出账钱包地址
        $transferWallet = $this->getBussiness('ProjectWallet')->getList($pro_no,$type=2);
        $this->view->transferWallet = $transferWallet;
        $i = 0;
        foreach ($transferWallet as $item) {
            $transferIds[$i] = $item['chain_id'];
            $i = $i+1;
        }

        $transferChains = $this->getChains($transferIds,2);
        $this->view->transferChains = $transferChains;


        //手续费钱包地址
        $feeWallet = $this->getBussiness('ProjectWallet')->getList($pro_no,$type=3);
        $this->view->feeWallet = $feeWallet;
        $i = 0;
        foreach ($feeWallet as $item) {
            $feeWalletIds[$i] = $item['chain_id'];
            $i = $i+1;
        }

        $feeChains = $this->getChains($feeWalletIds,3);
        $this->view->feeChains = $feeChains;

        //视图
        $this->view->pick('project/detail');

    }


    public function addWalletAction(){
        $type = $this->request->getPost('type');//区别钱包类型 1冷钱包，2出账钱包，3手续费钱包
        $chain = $this->request->getPost('chain_id');
        $data['chain_id'] = explode(',',$chain)[0];
        $data['chain_symbol'] = explode(',',$chain)[1];
        if($type == 1){
            $data['memo'] = $this->request->getPost('memo');
            $data['address'] = $this->request->getPost('address');
            //校验数据
            $validation = $this->paValidation;
            $validation->addlengWallet();
        }elseif($type == 2 && $data['chain_symbol'] == 'EOS'){
            $data['address'] = $this->request->getPost('address');
            //校验数据
            $validation = $this->paValidation;
            $validation->addEOSWallet();
        }else{
            $data['password'] = $this->request->getPost('password');
            //校验数据
            $validation = $this->paValidation;
            $validation->addWallet();
        }

        $messages = $validation->validate($data);
        if($type == 1){
            if(count($messages)){
                $message = $messages[0]->getMessage();
                $this->functions->alert($message);
            }
        }

        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        //密码小写
        if($type != 1 && $data['chain_symbol'] != 'EOS'){
            $data['password'] = strtolower($data['password']);
        }
        $add = $this->getBussiness('ProjectWallet')->add($type,$data);
        if($type == 1){
            $this->functions->alert($add['msg'],'/'.$pro_no.'/project/detail');
        }else{
            return json_encode($add);
        }

    }

    public function editWalletAction(){
        $id = $this->request->getPost('coldId');
        $data['address'] = $this->request->getPost('coldData');
        $data['password'] = strtolower($this->request->getPost('password'));
        $data['memo'] = $this->request->getPost('coldMemo');
        //校验数据
        $validation = $this->paValidation;
        $validation->editlengWallet();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        $edit = $this->getBussiness('ProjectWallet')->edit($id,$data);

        if($edit['status'] == -2){
            $this->functions->alert($edit['msg'],'/backend/logout');
        }
        if($edit['status'] != 1){
            $this->functions->alert($edit['msg']);
        }


        $this->functions->alert($edit['msg'],'/'.$pro_no.'/project/detail');
    }

    public function passwordConfirmAction(){
        $reqData['password'] = $this->request->get('password');
        $reqData['address'] = $this->request->get('address');
        $reqData['pro_no'] = $this->request->get('pro_no');
        //校验数据
        $validation = $this->paValidation;
        $validation->passwordConfirm();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $result = $this->getBussiness('ProjectWallet')->passwordConfirm($reqData);
        return json_encode($result);
    }

    public function getChains($ids,$flow_type){
        //手续费排除eos公链
        $eosChainId = $this->getBussiness('SystemChain')->getByWhere('chain_symbol','EOS');

        //获取公链 ，排除已经有冷钱包的
        $chains = $this->getBussiness('SystemChain')->getAll();
        foreach ($chains as $k=>$v){
            if($v['id'] == $eosChainId['id'] && $flow_type == 3){
                unset($chains[$k]);
            }
            if(in_array($v['id'],$ids)){
                //如果已经存在当前公链的冷钱包地址，就从数组中剔除
                unset($chains[$k]);
            }

//            $chains = array_values($chains);
        }
        return $chains;

    }

}