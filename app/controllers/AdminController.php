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
        $data['page'] = $this->request->get('page');
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
        $sql = "select `admin_name`,`admin_no`,`created_at`,`ip`,`server_name` from $table order by created_at desc limit $page,$limit";
        $list = $this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/log');
    }


    public function serverListAction(){
        $limit = 10;
        $page = $this->request->get('page');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_server';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        $sql = "select `id`,`server_name`,`url`,`created_at` from $table order by created_at desc limit $page,$limit";
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/serverList');
    }

    public function serverAddAction(){
        $reqData['server_name'] = $this->request->getPost('server_name');
        $reqData['url'] = $this->request->getPost('url');

        //校验数据
        $validation = $this->paValidation;
        $validation->serverAdd();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $add = $this->getBussiness('Server')->addServer($reqData);
        $this->functions->alert($add['msg'],'/admin/server/list');
    }

    public function serverDelAction(){
        $reqData['id'] = $this->request->getQuery('id');
        if(!isset($reqData['id']) || $reqData['id'] == ''){
            $this->functions->alert('信息丢失，修改失败');
        }

        $del = $this->getModel('Server')->delServer($reqData['id']);
        if(!$del){
            $this->functions->alert('删除失败');
        }

        $this->functions->alert('删除成功');
    }

}