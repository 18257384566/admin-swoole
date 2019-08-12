<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class AdminController extends ControllerBase
{

    public function getListAction(){
        //权限
        $admin = $this->dispatcher->getParam('admin');
        $this->view->adminName = $admin['account'];

        //adminList
        $limit = 10;
        $page = $this->request->get('page');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_admin';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        $sql = "select id,admin_name,real_name,phone,status,created_at,updated_at,role from $table order by created_at desc limit $page,$limit";
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount'] = $allcount['allcount'];
        $data['page'] = $page;
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/list');
    }

    public function updateStatusAction(){
        $reqData['id'] = $this->request->get('id');
        $reqData['status'] = $this->request->get('status');

        $update = $this->getBussiness('Admin')->updateStatus($reqData);

        if($update['status'] != 1){
            $this->functions->alert($update['msg']);
        }

        $this->functions->alert($update['msg'],'/admin/list');

    }

    public function addViewAction(){
        $admin = $this->dispatcher->getParam('admin');
        $this->view->adminName = $admin['account'];

        $this->view->permission = '1';
        $this->view->pick('admin/add');
    }

    public function addAdminAction(){
        $reqData['admin_name'] = $this->request->getPost('admin_name');
        $reqData['real_name'] = $this->request->getPost('real_name');
        $reqData['password'] = strtolower($this->request->getPost('password'));
        $reqData['phone'] = $this->request->getPost('phone');
        $reqData['role'] = $this->request->getPost('role');
        //$reqData['permissions'] = $this->request->getPost('permissions');
        //校验数据
        $validation = $this->paValidation;
        $validation->addAdmin();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $add = $this->getBussiness('Admin')->add($reqData);
        if($add['status']!=1){
            $this->functions->alert($add['msg']);
        }

        $this->functions->alert($add['msg'],'/admin/list');
    }

    public function adminLogAction(){
        $limit = 10;
        $page = $this->request->get('page');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_admin_login_log';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        $sql = "select admin_name,admin_no,created_at,ip from $table order by created_at desc limit $page,$limit";
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$page;
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/log');
    }





    public function addAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['admin_name'] = $this->request->getPost('admin_name');
        $reqData['real_name'] = $this->request->getPost('real_name');
        $reqData['password'] = strtolower($this->request->getPost('password'));
        $reqData['phone'] = $this->request->getPost('phone');
        $reqData['is_super'] = $this->request->getPost('is_super');
        $reqData['permissions'] = $this->request->getPost('permissions');
        //校验数据
        $validation = $this->paValidation;
        $validation->addAdmin();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $add = $this->getBussiness('Admin')->add($admin,$reqData);
        if($add['status']!=1){
            $this->functions->alert($add['msg']);
        }

        $this->functions->alert($add['msg'],'/admin/list');
    }









    public function getPermission(){
        //判断当前是否存在这个权限，如果没有则存入权限
        $data = [
            'name'=>'admin',
            'show_name'=>'项目管理员',
            'top_id'=>0,
        ];

        return $this->getBussiness('Permission')->add($data);

    }

    public function infoViewAction(){
        $this->view->pro_no = $this->dispatcher->getParam('pro_no');

        //权限
        $top_id = $this->getPermission();
        $data = [
            'name'=>'admin_list',
            'show_name'=>'管理员列表',
            'top_id'=>$top_id,
        ];
        $second_top_id = $this->getBussiness('Permission')->add($data);

        //do
        $datas = [
            'name'=>'admin_list_do',
            'show_name'=>'可操作',
            'top_id'=>$second_top_id,
        ];
        $do_id = $this->getBussiness('Permission')->add($datas);


        $adminPermission = $this->getBussiness('Permission')->getPermission($datas['name']);


        $id = $this->request->get('id');
        //遍历处理所有的数据
        $permissions = $this->getBussiness('ProjectPermission')->getAll();
        //redis取权限
//        $permissions = $this->getBussiness('RedisCache')->getPermissions();
        foreach ($permissions as $v){
//            $v = json_decode($v,true);
            $permission[$v['top_id']][] = ['id'=>$v['id'],'show_name'=>$v['show_name'],'name'=>$v['name']];
        }

        $admin = $this->getBussiness('Admin')->getById($id);
        $this->view->permission = $permission;
        $this->view->adminName = $adminPermission['account'];

        $this->view->admin = $admin;
        $this->view->pick('admin/info');
    }

    public function infoAction(){
        $id = $this->request->getPost('id');
        $data['admin_name'] = $this->request->getPost('admin_name');
        $data['real_name'] = $this->request->getPost('real_name');
        $data['phone'] = $this->request->getPost('phone');
        $data['is_power'] = $this->request->getPost('is_power');
        $data['permissions'] = $this->request->getPost('permissions');
        $data['password'] = strtolower($this->request->getPost('password'));


        //校验数据
        $validation = $this->paValidation;
        $validation->editAdmin();
        $messages = $validation->validate($data);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $adminSession = $this->session->get('backend');
        $pro_no = $adminSession['pro_no'];

        $update = $this->getBussiness('Admin')->info($pro_no,$id,$data);
        if($update['status']!=1){
            $this->functions->alert($update['msg']);
        }

        $this->functions->alert($update['msg'],'/'.$pro_no.'/admin/list');

    }

}