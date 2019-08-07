<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class WithdrawController extends ControllerBase
{
    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'withdraw',
            'show_name'=>'提币管理',
            'top_id'=>0,
        ];
        return $this->getBussiness('Permission')->add($data);

    }

    //出账钱包地址
    public function getTransferWallet($pro_no,$chain_id,$coin_type){
        $transferWallet = $this->getBussiness('ProjectWallet')->getByChainAndType($pro_no,$chain_id,2);
        if($transferWallet){
            $data['transferWallet'] = $transferWallet['address'];
            //币种余额
            $coinAsset = $this->getBussiness('ProjectWalletAsset')->getByAddressAndCoin($transferWallet['address'],$coin_type);
            $data['coinAsset'] = $coinAsset['coin_balance'];
            $data['feeAsset'] = $coinAsset['chain_fee_balance'];
        }

        return $data;
    }

    //待处理列表
    public function waitdealAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'withdraw_waitdeal',
            'show_name'=>'待审核',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'withdraw_waitdeal_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'withdraw_waitdeal_do',
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
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=20;

        $sql=" where status=0";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }


        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);

        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select id,address,memo,coin_amount,coin_id,created_at from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //总金额
        $allamount = $this->db->query("select sum(coin_amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();



        $thecoin = $this->getBussiness('ProjectCoin')->getById($coin_id);
        $data['coin_symbol'] = $thecoin['coin_symbol'];
        //公链
        $data['chain_symbol'] = $thecoin['chain_symbol'];

        //获取出账钱包
        $transferWallet = $this->getTransferWallet($adminSession['pro_no'],$thecoin['chain_id'],$data['coin_symbol']);
        $data['coin_id'] = $coin_id;
        $data['address'] = $address;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allamount'] = $allamount['allamount'];
        $data['transferWallet'] = $transferWallet;

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="coin_id=".$coin_id."&address=".$address."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="coin_id=".$coin_id."&address=".$address."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('withdraw/waitdeal');
    }

    public function waitdealExcelAction(){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');

        $sql=" where status=0";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if($startime > $endtime){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".$startime;
        }
        if($endtime){
            $sql.=" and created_at<".$endtime;
        }

        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);

        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $list=$this->db->query("select id,address,memo,coin_amount,coin_symbol,coin_id,created_at from ".$table.$sql.$orderBy);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        if($list == []){
            $this->functions->alert('没有数据导出');
        }

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'select';
        $sqlData['log_title'] = $adminSession['account'].'导出了提币待审核excel';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);


        //导表
        if($coin_id == ''){
            $coin_symbol = '';
        }else{
            $coin_symbol = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        }
        $outputFileName = '提币-待审核'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';
        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename='.$outputFileName); //设置导出的excel的名字
        header('Cache-Control: max-age=0');

        if($coin_symbol == 'EOS'){
            echo iconv("utf-8","gbk","ID号\t钱包地址\t标签\t金额\t操作时间\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['created_at'] = date('Y-m-d,H:i:s',$rs['created_at']);

                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['memo']}\t{$rs['coin_amount']}\t{$rs['created_at']}\n");
            }
        }else{
            echo iconv("utf-8","gbk","ID号\t钱包地址\t金额\t操作时间\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['created_at'] = date('Y-m-d,H:i:s',$rs['created_at']);

                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['coin_amount']}\t{$rs['created_at']}\n");
            }
        }

        exit;

    }

    //已拒绝列表
    public function refuseListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'withdraw_refuse',
            'show_name'=>'已拒绝',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'withdraw_refuse_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'withdraw_refuse_do',
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
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');
        $admin_no=$this->request->get('admin_no');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=20;

        $sql=" where status=3";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }

        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);
        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by updated_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select * from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //总金额
        $allamount = $this->db->query("select sum(coin_amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();


        $data['coin_symbol'] = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        //所有管理员
        $admin = $this->db->query("select admin_no,admin_name,id,created_at,updated_at from wallet_system_project_admin where pro_no = '".$adminSession['pro_no']."'".$orderBy);
        $admin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $data['admin'] = $admin->fetchAll();



        $data['admin_no'] = $admin_no;
        $data['coin_id'] = $coin_id;
        $data['address'] = $address;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allamount'] = $allamount['allamount'];

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="coin_id=".$coin_id."&address=".$address."&admin_no=".$admin_no."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="coin_id=".$coin_id."&address=".$address."&admin_no=".$admin_no."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('withdraw/refuse');
    }

    public function refuseExcelAction(){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');
        $admin_no=$this->request->get('admin_no');

        $sql=" where status=3";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if($startime > $endtime){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".$startime;
        }
        if($endtime){
            $sql.=" and created_at<".$endtime;
        }

        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);

        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }


        $sql .= " and pro_no = '".$adminSession['pro_no']."'";

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $list=$this->db->query("select id,address,memo,coin_amount,coin_symbol,coin_id,admin_name,refuse_remark,created_at,updated_at from ".$table.$sql.$orderBy);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        if($list == []){
            $this->functions->alert('没有数据导出');
        }

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'select';
        $sqlData['log_title'] = $adminSession['account'].'导出了提币拒绝excel';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);


        //导表
        //新建execl
//        error_reporting(0);
//        $resultPHPExcel = $this->PHPExcel;
////        钱包地址、金额、操作员、操作时间、拒绝理由、ID
//        //设置第一行
//        $resultPHPExcel->getActiveSheet()->setCellValue('A1', 'ID');
//        $resultPHPExcel->getActiveSheet()->setCellValue('B1', '钱包地址');
//        $resultPHPExcel->getActiveSheet()->setCellValue('C1', '金额');
//        $resultPHPExcel->getActiveSheet()->setCellValue('D1', '操作员');
//        $resultPHPExcel->getActiveSheet()->setCellValue('E1', '操作时间');
//        $resultPHPExcel->getActiveSheet()->setCellValue('F1', '拒绝理由');
//        //设值
//        $i = 2;
//        foreach ($list as $rule) {
//            $rule['created_at'] = date('Y-m-d,H:i:s',$rule['created_at']);
//            $rule['updated_at'] = date('Y-m-d,H:i:s',$rule['updated_at']);
//
//            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $rule['id']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $rule['address']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $rule['coin_amount']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $rule['admin_name']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, $rule['updated_at']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $rule['refuse_remark']);
//            $i++;
//        }
//        if($coin_id == ''){
//            $coin_symbol = '';
//        }else{
//            $coin_symbol = $rule['coin_symbol'];
//        }
//
//        //设置导出文件名
//        $outputFileName = '提币-已拒绝'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';
//        ob_end_clean();
//        header('Content-Type: application/vnd.ms-excel');
//        header('Content-Disposition: attachment; filename='.$outputFileName);
//        header('Cache-Control: max-age=0');
//        $ExcelWriter = \PHPExcel_IOFactory::createWriter($resultPHPExcel, 'Excel2007');
//        $ExcelWriter->save('php://output');


        if($coin_id == ''){
            $coin_symbol = '';
        }else{
            $coin_symbol = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        }

        //设置导出文件名
        $outputFileName = '提币-已拒绝'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';

        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename='.$outputFileName); //设置导出的excel的名字
        header('Cache-Control: max-age=0');

        echo iconv("utf-8","gbk","ID号\t钱包地址\t标签\t金额\t操作员\t操作时间\t拒绝理由\n");  //  \t是制表符 \n是换行符
        foreach ($list as $rs){   //$arr 是所要导出的数
            $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
            echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['memo']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\t{$rs['refuse_remark']}\n");
        }
        exit;


    }


    //提现中列表
    public function withdrawListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'withdraw_withdraw',
            'show_name'=>'提现中',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'withdraw_withdraw_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'withdraw_withdraw_do',
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
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');
//        $hash=$this->request->get('hash');
        $admin_no=$this->request->get('admin_no');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=20;

        $sql=" where status=1";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }

        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);
        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

//        if($hash){
//            $sql.=" and hash ='".$hash."'";
//        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by updated_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select * from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //总金额
        $allamount = $this->db->query("select sum(coin_amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();

        $data['coin_symbol'] = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        //所有管理员
        $admin = $this->db->query("select admin_no,admin_name,id,created_at from wallet_system_project_admin where pro_no = '".$adminSession['pro_no']."'".$orderBy);
        $admin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $data['admin'] = $admin->fetchAll();

        $data['admin_no'] = $admin_no;
        $data['coin_id'] = $coin_id;
        $data['address'] = $address;
//        $data['hash'] = $hash;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allamount'] = $allamount['allamount'];

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="coin_id=".$coin_id."&address=".$address."&admin_no=".$admin_no."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="coin_id=".$coin_id."&address=".$address."&admin_no=".$admin_no."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('withdraw/withdraw');
    }

    public function withdrawListExcelAction(){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');
        $admin_no=$this->request->get('admin_no');

        $sql=" where status=1";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if($startime > $endtime){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".$startime;
        }
        if($endtime){
            $sql.=" and created_at<".$endtime;
        }

        if(!$coin_id){
            $coin_id = 1;
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }


        $sql .= " and pro_no = '".$adminSession['pro_no']."'";

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $list=$this->db->query("select id,address,memo,coin_symbol,coin_amount,admin_name,created_at,updated_at from ".$table.$sql.$orderBy);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        if($list == []){
            $this->functions->alert('没有数据导出');
        }

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'select';
        $sqlData['log_title'] = $adminSession['account'].'导出了提币中excel';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);

        //导表
        if($coin_id == ''){
            $coin_symbol = '';
        }else{
            $coin_symbol = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        }

        //设置导出文件名
        $outputFileName = '提币-提现中'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';

        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename='.$outputFileName); //设置导出的excel的名字
        header('Cache-Control: max-age=0');

        if($coin_symbol == 'EOS'){
            echo iconv("utf-8","gbk","ID号\t钱包地址\t标签\t金额\t操作员\t操作时间\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['memo']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\n");
            }
        }else{
            echo iconv("utf-8","gbk","ID号\t钱包地址\t金额\t操作员\t操作时间\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\n");
            }
        }

        exit;
    }

    //已到账列表
    public function successListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'withdraw_success',
            'show_name'=>'已到账',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'withdraw_success_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'withdraw_success_do',
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
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');
//        $hash=$this->request->get('hash');
        $admin_no=$this->request->get('admin_no');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=20;

        $sql=" where status=2";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }

        //所有币种
        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);
        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

//        if($hash){
//            $sql.=" and hash ='".$hash."'";
//        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by updated_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select * from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //总金额
        $allamount = $this->db->query("select sum(coin_amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();

        $data['coin_symbol'] = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        //所有管理员
        $admin = $this->db->query("select admin_no,admin_name,id,created_at from wallet_system_project_admin where pro_no = '".$adminSession['pro_no']."'".$orderBy);
        $admin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $data['admin'] = $admin->fetchAll();

        $data['admin_no'] = $admin_no;
        $data['coin_id'] = $coin_id;
        $data['address'] = $address;
//        $data['hash'] = $hash;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allamount'] = $allamount['allamount'];

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="coin_id=".$coin_id."&address=".$address."&admin_no=".$admin_no."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="coin_id=".$coin_id."&address=".$address."&admin_no=".$admin_no."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('withdraw/success');
    }

    public function successListExcelAction(){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');
        $admin_no=$this->request->get('admin_no');

        $sql=" where status=2";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if($startime > $endtime){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".$startime;
        }
        if($endtime){
            $sql.=" and created_at<".$endtime;
        }

        if(!$coin_id){
            $coin_id = 1;
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }


        $sql .= " and pro_no = '".$adminSession['pro_no']."'";

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $list=$this->db->query("select id,address,memo,coin_symbol,coin_amount,admin_name,created_at,updated_at from ".$table.$sql.$orderBy);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        if($list == []){
            $this->functions->alert('没有数据导出');
        }

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'select';
        $sqlData['log_title'] = $adminSession['account'].'导出了提币到账excel';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);


        //导表
        if($coin_id == ''){
            $coin_symbol = '';
        }else{
            $coin_symbol = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        }

        //设置导出文件名
        $outputFileName = '提币-已到账'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';

        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename='.$outputFileName); //设置导出的excel的名字
        header('Cache-Control: max-age=0');

        if($coin_symbol == 'EOS'){
            echo iconv("utf-8","gbk","ID号\t钱包地址\t标签\t金额\t操作员\t操作时间\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['memo']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\n");
            }
        }else{
            echo iconv("utf-8","gbk","ID号\t钱包地址\t金额\t操作员\t操作时间\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\n");
            }
        }

        exit;

    }

    //失败列表
    public function failListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'withdraw_fail',
            'show_name'=>'提币失败',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'withdraw_fail_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);
        //do
        $datas = [
            'name'=>'withdraw_fail_do',
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
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');

        $page=$this->request->get('page');
        if(!$page){
            $page=1;
        }
        $limitnum=20;

        $sql=" where status=4";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if(strtotime($startime) > strtotime($endtime)){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".strtotime($startime);
        }
        if($endtime){
            $sql.=" and created_at<".strtotime($endtime);
        }

        $data['coin'] = $this->getBussiness('ProjectCoin')->getAll($adminSession['pro_no']);

        if(!$coin_id){
            $coin_id = $data['coin'][0]['id'];
        }

        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by updated_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select id,address,memo,coin_amount,coin_id,created_at,updated_at,remark from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //总金额
        $allamount = $this->db->query("select sum(coin_amount) as allamount from ".$table.$sql);
        $allamount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allamount = $allamount->fetch();


        $thecoin = $this->getBussiness('ProjectCoin')->getById($coin_id);
        $data['coin_symbol'] = $thecoin['coin_symbol'];
        //公链
        $data['chain_symbol'] = $thecoin['chain_symbol'];

        //获取出账钱包
        $transferWallet = $this->getTransferWallet($adminSession['pro_no'],$thecoin['chain_id'],$data['coin_symbol']);


        $data['coin_id'] = $coin_id;
        $data['address'] = $address;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);
        $data['allamount'] = $allamount['allamount'];
        $data['transferWallet'] = $transferWallet;

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="coin_id=".$coin_id."&address=".$address."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="coin_id=".$coin_id."&address=".$address."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('withdraw/fail');
    }

    public function failListExcelAction(){
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $coin_id=$this->request->get('coin_id');
        $address=$this->request->get('address');

        $sql=" where status=4";//0待审核，1提现中，2提现成功，3拒绝提现，4提现失败
        if($startime > $endtime){
            $this->functions->alert( '时间选择有误，结束时间应该大于开始时间');
        }
        if($startime){
            $sql.=" and created_at>".$startime;
        }
        if($endtime){
            $sql.=" and created_at<".$endtime;
        }

        if(!$coin_id){
            $coin_id = 1;

        }
        if($coin_id){
            $sql.=" and coin_id =".$coin_id;
        }

        if($address){
            $sql.=" and address ='".$address."' or memo = '".$address."'";
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_withdraw';

        $list=$this->db->query("select id,address,memo,coin_symbol,coin_amount,coin_id,admin_name,created_at,updated_at,remark from ".$table.$sql.$orderBy);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        if($list == []){
            $this->functions->alert('没有数据导出');
        }

        //记录日志
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = 'wallet_'.$sqlData['pro_no'].'_project_withdraw';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = 'select';
        $sqlData['log_title'] = $adminSession['account'].'导出了提币失败excel';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);


        //导表
        //新建execl
//        error_reporting(0);
//        $resultPHPExcel = $this->PHPExcel;
//
//        //设置第一行
//        $resultPHPExcel->getActiveSheet()->setCellValue('A1', 'ID');
//        $resultPHPExcel->getActiveSheet()->setCellValue('B1', '钱包地址');
//        $resultPHPExcel->getActiveSheet()->setCellValue('C1', '金额');
//        $resultPHPExcel->getActiveSheet()->setCellValue('D1', '操作时间');
//        $resultPHPExcel->getActiveSheet()->setCellValue('E1', '失败原因');
//        //设值
//        $i = 2;
//        foreach ($list as $rule) {
//            $rule['created_at'] = date('Y-m-d,H:i:s',$rule['created_at']);
//            $rule['updated_at'] = date('Y-m-d,H:i:s',$rule['updated_at']);
//
//            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $rule['id']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $rule['address']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $rule['coin_amount']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $rule['updated_at']);
//            $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $rule['remark']);
//            $i++;
//        }
//        if($coin_id == ''){
//            $coin_symbol = '';
//        }else{
//            $coin_symbol = $rule['coin_symbol'];
//        }
//
//        //设置导出文件名
//        $outputFileName = '提币-提现失败'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';
//        ob_end_clean();
//        header('Content-Type: application/vnd.ms-excel');
//        header('Content-Disposition: attachment; filename='.$outputFileName);
//        header('Cache-Control: max-age=0');
//        $ExcelWriter = \PHPExcel_IOFactory::createWriter($resultPHPExcel, 'Excel2007');
//        $ExcelWriter->save('php://output');

        if($coin_id == ''){
            $coin_symbol = '';
        }else{
            $coin_symbol = $this->getBussiness('ProjectCoin')->getById($coin_id)['coin_symbol'];
        }

        //设置导出文件名
        $outputFileName = '提币-提现失败'.$coin_symbol.date('Y',time()).date('m',time()).date('d',time()).date('H',time()).date('i',time()).date('s',time()).'.xls';

        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename='.$outputFileName); //设置导出的excel的名字
        header('Cache-Control: max-age=0');

        if($coin_symbol == 'EOS'){
            echo iconv("utf-8","gbk","ID号\t钱包地址\t标签\t金额\t操作员\t操作时间\t失败原因\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['memo']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\t{$rs['remark']}\n");
            }
        }else{
            echo iconv("utf-8","gbk","ID号\t钱包地址\t金额\t操作员\t操作时间\t失败原因\n");  //  \t是制表符 \n是换行符
            foreach ($list as $rs){   //$arr 是所要导出的数
                $rs['updated_at'] = date('Y-m-d,H:i:s',$rs['updated_at']);
                echo iconv("utf-8","gbk","{$rs['id']}\t{$rs['address']}\t{$rs['coin_amount']}\t{$rs['admin_name']}\t{$rs['updated_at']}\t{$rs['remark']}\n");
            }
        }

        exit;

    }


    //拒绝提现操作
    public function refuseAction(){
        $id = $this->request->getPost('id');
        $data['refuse_remark'] = $this->request->getPost('refuse_remark');
        //校验数据
        $validation = $this->paValidation;
        $validation->withdrawrefuse();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        $result = $this->getBussiness('Withdraw')->refuse($id,$data);
        if($result['status'] != 1){
            $this->functions->alert($result['msg']);
        }

        $this->functions->alert('处理成功','/'.$pro_no.'/withdraw/refuseList?coin_id='.$result['coin_id']);
    }

    //单个提现通过
    public function successAction(){
        $id = $this->request->getPost('id');
        $data['password'] = $this->request->getPost('password');


        //校验数据
        $validation = $this->paValidation;
        $validation->withdrawsuccess();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        $result = $this->getBussiness('Withdraw')->success($id,$data);
        if($result['status'] == -2){
            $this->functions->alert($result['msg'],'/'.$pro_no.'/logout');
        }elseif($result['status'] == -3){
            $this->functions->alert($result['msg'],'/'.$pro_no.'/project/detail');
        }elseif($result['status'] == -1){
            $this->functions->alert($result['msg']);
        }

        $this->functions->alert('处理成功','/'.$pro_no.'/withdraw/withdraw?coin_id='.$result['coin_id']);
    }


    //批量提现
    public function dealMoreOrdersAction(){
        $data['ids'] = $this->request->getPost('id');
        $data['coin_id'] = $this->request->getPost('coin_id');
        $data['password'] = $this->request->getPost('password');

        //校验数据
        $validation = $this->paValidation;
        $validation->dealmorewithdraw();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        $result = $this->getBussiness('Withdraw')->dealMoreOrders($data);
        if($result['status'] == -2){
            $this->functions->alert($result['msg'],'/'.$pro_no.'/logout');
        }elseif($result['status'] == -3){
            $this->functions->alert($result['msg'],'/'.$pro_no.'/project/detail');
        }elseif($result['status'] == -1){
            $this->functions->alert($result['msg']);
        }

        $this->functions->alert('处理成功','/'.$pro_no.'/withdraw/withdraw?coin_id='.$result['coin_id']);
    }


    //钱包调用更新充值/提现的接口
    public function dealOrderAction(){
        $data['send_type'] = $this->request->getPost('send_type');//deposit/withdraw
        $data['pro_no'] = $this->request->getPost('pro_no');
        $data['type'] = $this->request->getPost('type');
        $data['coin_type'] = $this->request->getPost('coin_type');
        $data['address'] = $this->request->getPost('address');
        $data['from_address'] = $this->request->getPost('from_address');
        $data['hash'] = $this->request->getPost('hash');
        $data['num'] = $this->request->getPost('num');
        $data['fee'] = $this->request->getPost('fee');
        $data['status'] = $this->request->getPost('status');
        $data['remark'] = $this->request->getPost('remark');
        $data['wallet_type'] = $this->request->getPost('wallet_type');
        //提现用
        $data['to_address'] = $this->request->getPost('to_address');

        //校验数据
        $validation = $this->paValidation;
        $validation->dealOrder();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        if($data['send_type'] == 'transaction'){
            //充值
            $result = $this->getBussiness('Deposit')->dealOrder($data);
        }elseif($data['send_type'] == 'withdraw'){
            $result = $this->getBussiness('Withdraw')->dealOrder($data);
        }

        return json_encode($result);

    }


}