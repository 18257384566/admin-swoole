<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class AdminlogController extends ControllerBase
{
    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'admin',
            'show_name'=>'项目管理员',
            'top_id'=>0,
        ];

        return $this->getBussiness('Permission')->add($data);

    }

    public function getListAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');
        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'admin_log',
            'show_name'=>'日志管理',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);
        //read
        $data = [
            'name'=>'admin_log_read',
            'show_name'=>'可查看',
            'top_id'=>$second_top_id,
        ];
        $id = $this->getBussiness('Permission')->add($data);


        $adminSession = $this->session->get('backend');
        $adminPermission = $this->getBussiness('Permission')->getPermission($data['name']);

        $data['is_super'] = $adminPermission['is_super'];$data['is_power'] = $adminPermission['is_power'];
        $data['permission'] = $adminPermission['permissions'];

        $this->view->adminName = $adminPermission['account'];
        $startime=$this->request->get('begin_date');
        $endtime=$this->request->get('end_date');
        $admin_no = $this->request->get('admin_no');
        $ip = $this->request->get('ip');

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

        if($admin_no){
            $sql.=" and admin_no =".$admin_no;
        }

        if($ip){
            $sql.=" and ip like ".'"%'.$ip.'%"';
        }

        $sql .= " and pro_no = '".$adminSession['pro_no']."'";


        $limit=" limit ".($limitnum*($page-1)).",".$limitnum;

        $orderBy = ' order by created_at desc,id desc ';

        $table = 'wallet_'.$adminSession['pro_no'].'_project_log';

        $allcount = $this->db->query("select count(id) as allcount from ".$table.$sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        $list=$this->db->query("select id,admin_name,log_title,ip,created_at from ".$table.$sql.$orderBy.$limit);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //所有管理员
        $admin = $this->db->query("select admin_no,admin_name,id,created_at from wallet_system_project_admin where pro_no = '".$adminSession['pro_no']."'".$orderBy);
        $admin->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $data['admin'] = $admin->fetchAll();

        $data['admin_no'] = $admin_no;
        $data['ip'] = $ip;
        $data['begin_date'] = strtotime($startime);
        $data['end_date'] = strtotime($endtime);

        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limitnum);

        $data['list']=$list;

        if(isset($_GET['begin_date']) && isset($_GET['end_date'])){
            $data["search"]="admin_no=".$admin_no."&ip=".$ip."&begin_date=".$_GET['begin_date']."&end_date=".$_GET['end_date']."&";
        }else{
            $data["search"]="admin_no=".$admin_no."&ip=".$ip."&begin_date=".strtotime($startime)."&end_date".strtotime($endtime)."&";
        }
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/log');
    }

    public function getInfoAction(){
        $pro_no = $this->dispatcher->getParam('pro_no');
        $this->view->pro_no = $pro_no;
        $adminSession = $this->session->get('backend');
        $this->view->adminName = $adminSession['account'];
        $id = $this->request->get('id');
        $info=$this->db->query("select id,admin_name,log_title,ip,`table`,`sql`,sql_type,created_at from wallet_".$pro_no."_project_log where pro_no='".$adminSession['pro_no']."' and id=".$id);
        $info->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $info = $info->fetch();
        if(!$info){
            $this->functions->alert('日志记录不存在');
        }
        $this->view->info = $info;
        $this->view->pick('admin/logInfo');
    }

}