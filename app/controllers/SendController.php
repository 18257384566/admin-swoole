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

    //发送道具（导表）
    public function tableSendViewAction(){
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

        $table = 'homepage_table_senditem_log';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        $sql = "select `type`,`admin_name`,`nickname`,`item`,`server_name`,`is_send`,`created_at` from $table order by created_at desc limit $page,$limit";
        $list = $this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        $data['server_list'] = $server_list;
        $data['senditem_log'] = $list;

        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';


        $this->view->data = $data;
        $this->view->pick('send/propTable');
    }

    public function tableSendAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['server_id'] = $this->request->getPost('server_id');
        $reqData['type'] = $this->request->getPost('type');
        $reqData['mailtitle'] = $this->request->getPost('mailtitle');
        $reqData['mailcontent'] = $this->request->getPost('mailcontent');

        //校验数据
        $validation = $this->paValidation;
        $validation->tableSend();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        //发送邮件
        $tableSend = $this->getBussiness('Send')->tableSend($admin,$reqData);
        $this->functions->alert($tableSend['msg']);
        exit;
    }

    public function tableAddViewAction(){
        $this->view->pick('send/propTableAdd');
    }

    public function tableAddAction(){
        $reqData['type'] = $this->request->getPost('type');
        if(!isset($reqData['type']) || $reqData['type'] == ''){
            $this->functions->alert('请输入唯一标识');
            exit;
        }

        //判断该标识是否被使用
        $filed = 'id';
        $isset = $this->getModel('SenditemTableLog')->getByType($reqData['type'],$filed);
        if($isset){
            $this->functions->alert('该标识已存在');
            exit;
        }

        header("Content-type: text/html; charset=utf-8");
        //判断是否上传文件
        if(!isset($_FILES['file'])){
            $this->functions->alert('请上传文件');
        }

        //判断上传文件是否合法
        $filename = $_FILES['file']['tmp_name'];
        $name = strstr( $_FILES['file']['name'], '.');
        if($name != '.csv'){
            $this->functions->alert('导入文件格式只能为csv');
        }
        if (empty ($filename)) {
            $this->functions->alert('请选择要导入的CSV文件');
        }

        //打开上传文件
        $handle = fopen($filename, 'r');
        $result = $this->functions->input_csv($handle); //解析csv
        $len_result = count($result);
        if($len_result == 0){
            $this->functions->alert('没有任何数据');
        }

        //遍历表格数据
        $time = time();
        for ($i = 0; $i < $len_result; $i++) { //循环获取各字段值
            $reqData['nickname'] = iconv('gb2312', 'utf-8', $result[$i][0]); //中文转
            $reqData['item'] = iconv('gb2312', 'utf-8', $result[$i][1]);

            //判断数据是否为空
            if(!isset($reqData['nickname']) || !isset($reqData['item']) || $reqData['nickname'] == '' || $reqData['item'] == ''){
                continue;
            }

            //存入数据库
            $sql = "insert into homepage_table_senditem_log (`type`,`nickname`,`item`,`created_at`,`updated_at`) VALUES (?,?,?,?,?)";
            $params = array(
                $reqData['type'],
                $reqData['nickname'],
                $reqData['item'],
                $time,
                $time
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/manager/prop/tableSendAdd');
    }

}