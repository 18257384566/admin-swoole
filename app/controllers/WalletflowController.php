<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class WalletflowController extends ControllerBase
{
    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'wallet_flow',
            'show_name'=>'钱包流水',
            'top_id'=>0,
        ];

        return $this->getBussiness('Permission')->add($data);

    }

    public function getListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'flow',
            'show_name'=>'钱包流水',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'flow_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'flow_do',
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
        $coin_id = $this->request->get('coin_id');
        $hash = $this->request->get('hash');
        $flow_type = $this->request->get('flow_type');
        $status = $this->request->get('status');
        $address = $this->request->get('address');

        //提现管理-详情
        $obj_id = $this->request->get('obj_id');



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

        if($hash){
            $sql.=" and hash ='".$hash."'";
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and (from_address =".'"'.$address.'"'." or to_address =".'"'.$address.'")';
        }

        if($flow_type!=null){
            $sql.=" and flow_type =".$flow_type;
        }




        if($obj_id){
            $sql.=" and obj_name='wallet_".$adminSession['pro_no']."_project_withdraw' and obj_id=".$obj_id;
        }


        if($status!=null){
            $sql.=" and status =".$status;
        }else{
            $sql.=" and status = 1";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";

//        $sqlstatus = '';
//        if($status!=null){
//            $sqlstatus.=" and status =".$status;
//        }else{
//            $sqlstatus.=" and status = 1";
//        }


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_wallets_flow';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //计算总和(成功的)
        $allamount = $this->db->query("select sum(coin_amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();

        $fee = $this->db->query("select sum(coin_chain_amount) as fee from ".$table.$sql);
        $fee->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $fee = $fee->fetch();

        $list=$this->db->query("select id,from_address,to_address,status,hash,chain_symbol,coin_amount,coin_chain_amount,flow_type,coin_symbol,created_at,updated_at from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);

        if($coin_id){
            //汇总前面的币种名
            $coin= $this->getBussiness('ProjectCoin')->getByWhere($adminSession['pro_no'],'id',$coin_id);
            //公链信息
            $chain = $this->getBussiness('SystemChain')->getByWhere('id',$coin[0]['chain_id']);
            $data['coin_symbol'] = $coin[0]['coin_symbol'];
            $data['blockchain'] = $chain['blockchain'];
        }
        $data['allamount'] = $allamount['allamount'];
        $data['coin_id'] = $coin_id;
        $data['hash'] = $hash;
        $data['address'] = $address;
        $data['flow_type'] = $flow_type;
        $data['status'] = $status;
        $data['obj_id'] = $obj_id;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allcount']=$allcount['allcount'];
        $data['fee']=$fee['fee'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="hash=".$hash."&status=".$status."&coin_id=".$coin_id."&address=".$address."&flow_type=".$flow_type."&obj_id=".$obj_id."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="hash=".$hash."&status=".$status."&coin_id=".$coin_id."&address=".$address."&flow_type=".$flow_type."&obj_id=".$obj_id."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('walletflow/list');
    }

    public function getExcelAction(){
        $adminSession = $this->session->get('backend');

        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $coin_id = $this->request->get('coin_id');
        $hash = $this->request->get('hash');
        $flow_type = $this->request->get('flow_type');
        $status = $this->request->get('status');
        $address = $this->request->get('address');

        //提现管理-详情
        $obj_id = $this->request->get('obj_id');



        $sql=" where 1=1";
        if($startime > $endtime){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".$startime;
        }
        if($endtime){
            $sql.=" and created_at<".$endtime;
        }

        if($hash){
            $sql.=" and hash ='".$hash."'";
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }


        if($flow_type!=null){
            $sql.=" and flow_type =".$flow_type;
        }

        if($status!=null){
            $sql.=" and status =".$status;
        }

        if($address){
            $sql.=" and (from_address =".'"'.$address.'"'." or to_address =".'"'.$address.'")';
        }

        if($obj_id){
            $sql.=" and obj_name='wallet_".$adminSession['pro_no']."_project_withdraw' and obj_id=".$obj_id;
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";




        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_wallets_flow';

        $list=$this->db->query("select id,from_address,to_address,chain_symbol,coin_symbol,hash,coin_amount,coin_chain_amount,flow_type,status,created_at,updated_at from ".$table.$sql.$orderBy);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();



        if($list == []){
            $this->functions->alert('没有数据导出');
        }


        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_wallets_flow';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'select';
        $sqlData['log_title'] = $adminSession['account'].'操作了钱包流水导出excel';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);


        //导表
        if($coin_id == ''){
            $coin_symbol = '';
        }else{
            $coin_symbol = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        }

        //设置导出文件名
        $outputFileName = '钱包流水-'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';

        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename='.$outputFileName); //设置导出的excel的名字
        header('Cache-Control: max-age=0');

        echo iconv("utf-8","gbk","ID号\t币种\t出账地址\t收款地址\thash\t金额\t矿工费\t类型\t状态\t操作时间\n");  //  \t是制表符 \n是换行符
        foreach ($list as $rs){   //$arr 是所要导出的数
            if($rs['flow_type'] == 1){
                $rs['flow_type'] = '收入';
            }elseif($rs['flow_type'] == 0){
                $rs['flow_type'] = '支出';
            }
            if($rs['status'] == 1){
                $rs['status'] = '正常';
            }elseif($rs['status'] == 0){
                $rs['status'] = '失败';
            }
            $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
            echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['coin_symbol']}\t{$rs['from_address']}\t{$rs['to_address']}\t{$rs['hash']}\t{$rs['coin_amount']}\t{$rs['coin_chain_amount']}\t{$rs['flow_type']}\t{$rs['status']}\t{$rs['updated_at']}\n");
        }
        exit;

    }



    //记录充值手续费钱包流水
    public function addFeeAction(){
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['from_address'] = $this->request->getPost('from_address');
        $data['to_address'] = $this->request->getPost('to_address');
        $data['type'] = $this->request->getPost('type');
        $data['coin_type'] = $this->request->getPost('coin_type');
        $data['hash'] = $this->request->getPost('hash');
        $data['num'] = $this->request->getPost('num');
        $data['fee'] = $this->request->getPost('fee');
        $data['status'] = $this->request->getPost('status');

        //校验数据
        $validation = $this->paValidation;
        $validation->addFee();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $result = $this->getBussiness('Deposit')->addFee($data);
        return json_encode($result);


    }


}