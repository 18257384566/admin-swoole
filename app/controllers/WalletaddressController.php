<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class WalletaddressController extends ControllerBase
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
            'name'=>'wallet_list',
            'show_name'=>'钱包列表',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'wallet_list_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'wallet_list_do',
            'show_name'=>'可操作',
            'top_id'=>$second_top_id,
        ];
        $do_id = $this->getBussiness('Permission')->add($datas);

        $adminPermission = $this->getBussiness('Permission')->getPermission($data['name']);

        //将权限传人视图
        $data['is_super'] = $adminPermission['is_super'];$data['is_power'] = $adminPermission['is_power'];
        $data['permission'] = $adminPermission['permissions'];


        //列表页传来的(地址-详情)
        $batch_no = $this->request->get('batch_no');
        $chain_id = $this->request->get('chain_id');

        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $coin_id = $this->request->get('coin_id');
        $address = $this->request->get('address');
        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=10;

        $sql=" where batch_no = '".$batch_no."'";


        if($coin_id!=null){
            $sql.=" and coin_id = ".$coin_id;
            $table = 'wallet_'.$adminSession['pro_no'].'_project_wallets_assets';
        }else{
            $table = 'wallet_'.$adminSession['pro_no'].'_project_user_wallets';
        }


        if($address){
            $sql.=" and address ='".$address."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';



        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select * from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();


        if($table == 'wallet_'.$adminSession['pro_no'].'_project_wallets_assets'){
            //计算总和
            $allamount = $this->db->query("select sum(coin_balance) as allamount from ".$table.$sql);
            $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $allamount = $allamount->fetch();

            $data['allamount']=$allamount['allamount'];
        }



        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'],'chain_id',$chain_id);
        $data['coin_symbol'] = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];

        $data['address'] = $address;
        $data['chain_id'] = $chain_id;
        $data['batch_no'] = $batch_no;
        $data['coin_id'] = $coin_id;
        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        $data["search"]="batch_no=".$batch_no."&chain_id=".$chain_id."&";

        $this->view->list = $list;
        $this->view->data = $data;
        if($table == 'wallet_'.$adminSession['pro_no'].'_project_wallets_assets'){
            $this->view->pick('walletaddress/asset');
        }else{
            $this->view->pick('walletaddress/info');
        }

    }

    //生成钱包地址
    public function addViewAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'wallet_add',
            'show_name'=>'生成钱包',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //do
        $data = [
            'name'=>'wallet_add_do',
            'show_name'=>'可操作',
            'top_id'=>$second_top_id,
        ];
        $do_id = $this->getBussiness('Permission')->add($data);

        $adminPermission = $this->getBussiness('Permission')->getPermission($data['name']);

        $chains = $this->getBussiness('SystemChain')->getAll();

        $this->view->adminName = $adminPermission['account'];
        $this->view->chains = $chains;
        $this->view->pick('walletaddress/add');
    }

    public function addAction(){
        $chain = $this->request->getPost('chain_id');
        $data['chain_id'] = explode(',',$chain)[0];
        $data['chain_symbol'] = explode(',',$chain)[1];
        $data['count'] = $this->request->getPost('count');
        $data['password1'] = $this->request->getPost('password1');
        $data['password2'] = $this->request->getPost('password2');
        $data['password3'] = $this->request->getPost('password3');
        $data['password_prompt'] = $this->request->getPost('password_prompt');
        $data['length'] = strlen($data['password1']) + strlen($data['password2']) + strlen($data['password3']);
        //校验数据
        $validation = $this->paValidation;
        $validation->addWalletaddress($data);
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $data['password1'] = strtolower($data['password1']);
        $data['password2'] = strtolower($data['password2']);
        $data['password3'] = strtolower($data['password3']);
        $result = $this->getBussiness('ProjectUserWallet')->add($data);
        if($result['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $result['msg'];
            return json_encode($this->result);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = $result['msg'];
        $this->result['data'] = $result['data'];
        return json_encode($this->result);

    }

    //钱包通知后台已完成生成钱包地址
    public function addUserWalletAction(){
        //获取参数
        $data['batch_no'] = $this->request->getPost('batch_no');
        $data['transactionId'] = $this->request->getPost('transactionId');
        $data['chain_symbol'] = $this->request->getPost('chain_symbol');
        $data['address'] = $this->request->getPost('address');
        $data['status'] = $this->request->getPost('status');

        //校验数据
        $validation = $this->paValidation;
        $validation->addUserWalletApi($data);
        $messages = $validation->validate($data);

        //如果存在错误信息
        if (count($messages)) {
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $transfer = $this->getBussiness('ProjectUserWallet')->addWalletNotice($data);

        return json_encode($transfer);

    }

    //转出冷钱包
    public function transferColdWalletAction(){
        //批次id
        $batch_id = $this->request->getPost('batch_id');
        $data['password1'] = $this->request->getPost('password1');
        $data['password2'] = $this->request->getPost('password2');
        $data['password3'] = $this->request->getPost('password3');
        $data['chain_symbol'] = $this->request->getPost('chain_symbol');
        //EOS
        if($data['chain_symbol'] == 'EOS'){
            $data['transferwallet_password'] = $this->request->getPost('transferwallet_password');
            //校验数据
            $validation = $this->paValidation;
            $validation->transferEOSColdWallet();
        }else{
            //校验数据
            $validation = $this->paValidation;
            $validation->transferColdWallet();
        }

        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $data['password1'] = strtolower($data['password1']);
        $data['password2'] = strtolower($data['password2']);
        $data['password3'] = strtolower($data['password3']);

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];
        $transfer = $this->getBussiness('ProjectUserWallet')->transfer($batch_id,$data);

        if($transfer['status'] == -2){
            $this->functions->alert($transfer['msg'],'/'.$pro_no.'/logout');
        }elseif($transfer['status'] == -3){
            $this->functions->alert($transfer['msg'],'/'.$pro_no.'/project/detail');
        }elseif($transfer['status'] == -1){
            $this->functions->alert($transfer['msg']);
        }

        $this->functions->alert('操作成功','/'.$pro_no.'/walletaddress/transferList');
    }



    //转出历史列表
    public function transferListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'wallet_list',
            'show_name'=>'钱包列表',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'wallet_list_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'wallet_list_do',
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

        $batch_no = $this->request->get('batch_no');
        $admin_no = $this->request->get('admin_no');
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=5;

        $sql=" where title='转出冷钱包'";
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }

        if($batch_no){
            $sql.=" and batch_no ='".$batch_no."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by transaction_no desc ';

        $groupby = ' group by transaction_no ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_transaction';

        $allcount = $this->db->query("select count(*) as allcount from (select count(id) from ".$table.$sql.$groupby.") as amount");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $transaction_no_list = $this->db->query("select transaction_no from ".$table.$sql.$groupby.$orderBy.$limit);
        $transaction_no_list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $transaction_no_list = $transaction_no_list->fetchAll();
//        dd($transaction_no_list);



        $arrList = array();
        if($transaction_no_list) {
            $transaction_no_str = "";
            foreach ($transaction_no_list as $v) {
                $transaction_no_str .= ",'" . $v['transaction_no'] . "'";
            }
            $transaction_no_str = ltrim($transaction_no_str, ',');
            $list = $this->db->query("select * from " . $table . " where transaction_no in($transaction_no_str) " . $orderBy);
            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $list = $list->fetchAll();

            foreach ($list as $value) {
                //查询批次
                $batchWallet = $this->getBussiness('ProjectWalletBatch')->getByWhere($adminSession['pro_no'],'batch_no',$value['batch_no']);
                $arrList[$value['transaction_no']]['password_prompt'] = $batchWallet[0]['password_prompt'];//冷钱包密码提示
                $arrList[$value['transaction_no']]['transaction_no'] = $value['transaction_no'];
                $arrList[$value['transaction_no']]['batch_no'] = $value['batch_no'];
                $arrList[$value['transaction_no']]['admin_name'] = $value['admin_name'];
                $arrList[$value['transaction_no']]['created_at'] = $value['created_at'];
                $arrList[$value['transaction_no']]['status'] = $value['status'];
                $arrList[$value['transaction_no']]['data'][] = $value;

                //计算事务里的未完成数量
//                $incomplete = $this->db->query("select sum(incomplete) as incomplete from " . $table . " where transaction_no = '".$value['transaction_no']."' and batch_no='".$value['batch_no']."'" . $orderBy);
//                $incomplete->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
//                $incomplete = $incomplete->fetch();
//                $arrList[$value['transaction_no']]['incomplete'] = $incomplete['incomplete'];

                //计算事务里的错误数量
                $fail = $this->db->query("select sum(fail) as fail from " . $table . " where transaction_no = '".$value['transaction_no']."' and batch_no='".$value['batch_no']."'" . $orderBy);
                $fail->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
                $fail = $fail->fetch();
                $arrList[$value['transaction_no']]['fail'] = $fail['fail'];
            }
        }

        //所有管理员
        $admin = $this->db->query("select admin_no,admin_name,id,created_at from wallet_system_project_admin where pro_no = '".$adminSession['pro_no']."'");
        $admin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $data['admin'] = $admin->fetchAll();


        $data['admin_no'] = $admin_no;
        $data['batch_no'] = $batch_no;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);


        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="admin_no=".$admin_no."&batch_no=".$batch_no."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="admin_no=".$admin_no."&batch_no=".$batch_no."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }

        $this->view->list = $arrList;
        $this->view->data = $data;
        $this->view->pick('wallettransaction/wallettransferlist');
    }


    //转出历史详情
    public function transferInfoAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];

        //将权限传人视图
        $adminRedis = $this->redis->hGetAll($adminSession['pro_no'].':'.$adminSession['admin_no']);
        $data['is_super'] = $adminRedis['is_super'];
        $data['is_power'] = $adminRedis['is_power'];
        $data['permission'] = $adminRedis['permissions'];

        $coin_id = $this->request->get('coin_id');
        $hash = $this->request->get('hash');
        $address = $this->request->get('address');
        $status = $this->request->get('status');
        $batch_no = $this->request->get('batch_no');
        $transaction_no = $this->request->get('transaction_no');

        //当前事务所有的coin
        $transaction_no_list = $this->getBussiness('ProjectTransactionInfo')->getByWhere($adminSession['pro_no'],'transaction_no',$transaction_no);
        $coins = array();
        if($transaction_no_list){
            foreach ($transaction_no_list as $k=>$v){
                if(in_array($v['coin_id'].','.$v['coin_symbol'],$coins)){
                    continue;
                }
                $coins[] = $v['coin_id'].','.$v['coin_symbol'];
            }
            if(!$coin_id){
                $coin_id = explode(',',$coins[0])[0];
            }
        }

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=10;

        $sql=" where transaction_no = '".$transaction_no."'";

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }
        if($address){
            $sql.=" and wallet_address ='".$address."'";
        }
        if($hash){
            $sql.=" and hash ='".$hash."'";
        }
        if($status!=null){
            $sql.=" and status ='".$status."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_transaction_info';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select id,amount,fee,wallet_address,hash,status,remark from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //币种信息
        $coin = $this->getBussiness('ProjectCoin')->getById($coin_id);
        //汇总
        $allamount = $this->db->query("select sum(amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();

        $fee = $this->db->query("select sum(fee) as fee from ".$table.$sql);
        $fee->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $fee = $fee->fetch();

        $data['transaction_no'] = $transaction_no;
        $data['batch_no'] = $batch_no;
        $data['coins'] = $coins;
        $data['address'] = $address;
        $data['hash'] = $hash;
        $data['status'] = $status;
        $data['coin_id'] = $coin_id;
        $data['coin'] = $coin;
        $data['allamount'] = $allamount['allamount'];
        $data['fee'] = $fee['fee'];

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        $data["search"]="coin_id=".$coin_id."&address=".$address."&hash=".$hash."&transaction_no=".$transaction_no."&batch_no=".$batch_no."&status=".$status."&";

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('wallettransaction/wallettransferinfo');
    }

    //失败重转
    public function transferSecondColdWalletAction(){
        //事务号
        $transaction_no = $this->request->getPost('transaction_no');
        $data['password1'] = strtolower($this->request->getPost('password1'));
        $data['password2'] = strtolower($this->request->getPost('password2'));
        $data['password3'] = strtolower($this->request->getPost('password3'));
        $data['coin_symbol'] = $this->request->getPost('coin_symbol');

        //EOS
        if($data['coin_symbol'] == 'EOS'){
            $data['transferwallet_password'] = $this->request->getPost('transferwallet_password');
            //校验数据
            $validation = $this->paValidation;
            $validation->transferEOSColdWallet();
        }else {
            //校验数据
            $validation = $this->paValidation;
            $validation->transferColdWallet();
        }


        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];
        $transfer = $this->getBussiness('ProjectUserWallet')->transferSecond($transaction_no,$data);

        if($transfer['status'] == -2){
            $this->functions->alert($transfer['msg'],'/'.$pro_no.'/logout');
        }elseif($transfer['status'] == -3){
            $this->functions->alert($transfer['msg'],'/'.$pro_no.'/project/detail');
        }elseif($transfer['status'] == -1){
            $this->functions->alert($transfer['msg']);
        }

        $this->functions->alert('操作成功','/'.$pro_no.'/walletaddress/transferList');
    }

    //api 转出冷钱包后的操作
    public function transferToPurseAction(){
        //获取参数
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['type'] = $this->request->getPost('type');
        $data['coin_type'] = $this->request->getPost('coin_type');
        $data['hash'] = $this->request->getPost('hash');
        $data['address'] = $this->request->getPost('address');//用户地址
        $data['from_address'] = $this->request->getPost('from_address');//用户地址
        $data['leng'] = $this->request->getPost('leng');//冷钱包地址
        $data['num'] = $this->request->getPost('num');
        $data['fee'] = $this->request->getPost('fee');
        $data['status'] = $this->request->getPost('status');

        $data['scenario'] = $this->request->getPost('scenario');//financeWithdrawlByhash

        if(isset($data['scenario'])){
            $validation = $this->paValidation;
            $validation->finishUnknownCoinTransfer();
            $messages = $validation->validate($data);

            //如果存在错误信息
            if (count($messages)) {
                $message = $messages[0]->getMessage();
                $this->result['status'] = -1;
                $this->result['msg'] = $message;
                return json_encode($this->result);
            }
            $transfer = $this->getBussiness('UnknownCoin')->finishTransfer($data);

        }else{
            $data['transaction_no'] = $this->request->getPost('transaction_no');
            $data['batch_no'] = $this->request->getPost('batch_no');
            
            //校验数据
            $validation = $this->paValidation;
            $validation->transferToPurse();
            $messages = $validation->validate($data);

            //如果存在错误信息
            if (count($messages)) {
                $message = $messages[0]->getMessage();
                $this->result['status'] = -1;
                $this->result['msg'] = $message;
                return json_encode($this->result);
            }

            if($data['type'] == 'EOS'){
                $transfer = $this->getBussiness('ProjectTransaction')->transferEOSToPurse($data);
            }else{
                $transfer = $this->getBussiness('ProjectTransaction')->transferToPurse($data);
            }
        }

        return json_encode($transfer);

    }

    //钱包通知后台已完成转出
    public function finishTransferToPurseAction(){
        //获取参数
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['transaction_no'] = $this->request->getPost('transaction_no');

        //校验数据
        $validation = $this->paValidation;
        $validation->finishTransferToPurse();
        $messages = $validation->validate($data);

        //如果存在错误信息
        if (count($messages)) {
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $transfer = $this->getBussiness('ProjectTransaction')->finishTransferToPurse($data);

        return json_encode($transfer);

    }

    //通知项目方充值手续费钱包
    public function addFeeNoticeAction(){
        //获取参数
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['chain_symbol'] = $this->request->getPost('chain_symbol');
        $data['coin_symbol'] = $this->request->getPost('coin_symbol');
        $data['transaction_no'] = $this->request->getPost('transaction_no');
        $data['status'] = $this->request->getPost('status');
        $data['remark'] = $this->request->getPost('remark');
        $data['address'] = $this->request->getPost('address');


        //校验数据
        $validation = $this->paValidation;
        $validation->addFeeNotice();
        $messages = $validation->validate($data);

        //如果存在错误信息
        if (count($messages)) {
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $notice = $this->getBussiness('ProjectUserWallet')->addFeeNotice($data);

        return json_encode($notice);

    }

}