<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class SendController extends ControllerBase
{
    public function propViewAction(){
        $admin = $this->dispatcher->getParam('admin');
        $server_url = $admin['server_url'];

        $limit = 10;
        $page = $this->request->get('page');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_split_senditem_log';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table where server_url = '$server_url'");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        $sql = "select `id`,`admin_name`,`nickname`,`item`,`server_name`,`is_success`,`created_at` from $table where server_url = '$server_url' order by created_at desc limit $page,$limit";
        $list = $this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        $data['admin'] = $admin;
        $data['senditem_log'] = $list;

        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';


        $this->view->data = $data;
        $this->view->server_url = $admin['server_url'];
        $this->view->pick('send/prop');
    }

    public function propAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['zones'] = $this->request->getPost('zone');
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['mailtitle'] = $this->request->getPost('mailtitle');
        $reqData['mailcontent'] = $this->request->getPost('mailcontent');
        $reqData['itemSelected'] = $this->request->getPost('itemSelected');

        //校验数据
        $validation = $this->paValidation;
        $validation->propSend();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        $propSend = $this->getBussiness('Send')->propSend($admin,$reqData);

        $this->functions->alert($propSend['msg']);
        return $this->dispatcher->forward(array(
            "controller" => "send",
            "action" => "propView",
        ));
    }

    public function noticeViewAction(){
        $admin = $this->dispatcher->getParam('admin');

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('send/notice');
    }

    public function propServerViewAction(){
        $admin = $this->dispatcher->getParam('admin');

        //查询服务器列表
        $server_list = $this->getModel('Server')->getList();
        if(!$server_list){
            $server_list = [];
        }

        //查询发送道具的记录
        $limit = 10;
        $page = $this->request->get('page');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_senditem_log';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        $sql = "select `id`,`admin_name`,`nickname`,`item`,`server_name`,`is_success`,`created_at` from $table order by created_at desc limit $page,$limit";
        $list = $this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        $data['admin'] = $admin;
        $data['server_list'] = $server_list;
        $data['senditem_log'] = $list;

        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->server_url = $admin['server_url'];
        $this->view->data = $data;
        $this->view->pick('send/propServer');
    }

    public function propServerAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['server_id'] = $this->request->getPost('server_id');
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['mailtitle'] = $this->request->getPost('mailtitle');
        $reqData['mailcontent'] = $this->request->getPost('mailcontent');
        $reqData['itemSelected'] = $this->request->getPost('itemSelected');

        //校验数据
        $validation = $this->paValidation;
        $validation->propServer();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        $propServer = $this->getBussiness('Send')->propServer($admin,$reqData);

        $this->functions->alert('操作完成');
        return $this->dispatcher->forward(array(
            "controller" => "send",
            "action" => "propServerView",
        ));
    }

}