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

        if($reqData['nickname'] == '@all'){
            $reqData['nickname'] = '';
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

        if($reqData['nickname'] == '@all'){
            $reqData['nickname'] = '';
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
        $sql = "select `no`,`type`,`admin_name`,`nickname`,`item`,`server_name`,`is_send`,`created_at` from $table order by created_at,no desc limit $page,$limit";
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
//            $reqData['no'] = iconv('GBK', 'utf-8', $result[$i][0]); //中文转
//            $reqData['nickname'] = iconv('GBK', 'utf-8', $result[$i][1]);
//            $reqData['item'] = iconv('GBK', 'utf-8', $result[$i][2]);

            $reqData['no'] = (int)mb_convert_encoding($result[$i][0], "UTF-8", "auto");
            $reqData['nickname'] = mb_convert_encoding($result[$i][1], "UTF-8", "auto");
            $reqData['item'] = mb_convert_encoding($result[$i][2], "UTF-8", "auto");

            //判断数据是否为空
            if(!isset($reqData['nickname']) || !isset($reqData['item']) || $reqData['nickname'] == '' || $reqData['item'] == ''){
                continue;
            }

            //存入数据库
            $sql = "insert into homepage_table_senditem_log (`no`,`type`,`nickname`,`item`,`created_at`,`updated_at`) VALUES (?,?,?,?,?,?)";
            $params = array(
                $reqData['no'],
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

    public function sendTableExcelAction(){
        $type = $this->request->getQuery('type');
        $table = 'homepage_table_senditem_log';
        $sql = "select `no`,`nickname`,`item`,`is_send` from $table where `type` = '$type' order by created_at,no asc";
        $list = $this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll(); //var_dump($list);exit;
        if(!$list){
            $this->functions->alert('暂无数据');
            exit;
        }
        //var_dump($list);exit;

        //导表
        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename="道具表-'.time().'.xls"'); //设置导出的excel的名字
        header('Cache-Control: max-age=0');
        set_time_limit (0);

        echo iconv("utf-8","gb2312","下标\t昵称\t道具\t是否成功\n");  //  \t是制表符 \n是换行符
        foreach ($list as $v){   //$arr 是所要导出的数
            if($v['is_send'] == 0){
                $is_send = '未发送';
            }else{
                $is_send = '已发送';
            }

            $v['no'] = (string)$v['no'];
            $v['nickname'] = (string)$v['nickname'];
            $v['item'] = (string)$v['item'];

            echo iconv("utf-8","gb2312","{$v['no']}\t{$v['nickname']}\t{$v['item']}\t{$is_send}\n");
        }
        exit;
    }


    //道具请求
    public function propRequestViewAction(){
        $admin = $this->dispatcher->getParam('admin');
        $server_url = $admin['server_url'];

        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_request_senditem_log';
        //获取总条数
        if(!isset($search) || $search == ''){
            $allcount = $this->db->query("select count(id) as allcount from $table");
        }else{
            $arr = [-1,0,1,2];
            if(in_array($search,$arr)){
                $allcount = $this->db->query("select count(id) as allcount from $table where `is_send`= $search");
            }else{
                $allcount = $this->db->query("select count(id) as allcount from $table `nickname`= $search");
            }
        }

        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`req_admin_name`,`deal_admin_name`,`nickname`,`item`,`server_name`,`diserver_id`,`remark`,`is_send`,`mailcontent`,`mailtitle` from $table order by created_at desc limit $page,$limit";
        }else{
            $arr = [-1,0,1,2];
            if(in_array($search,$arr)){
                $sql = "select `id`,`req_admin_name`,`deal_admin_name`,`nickname`,`item`,`server_name`,`diserver_id`,`remark`,`is_send`,`mailcontent`,`mailtitle` from $table where `is_send`= $search order by created_at desc limit $page,$limit";
            }else{
                $sql = "select `id`,`req_admin_name`,`deal_admin_name`,`nickname`,`item`,`server_name`,`diserver_id`,`remark`,`is_send`,`mailcontent`,`mailtitle` from $table where `nickname`= $search order by created_at desc limit $page,$limit";
            }
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "search=$search&";

        $this->view->server_url = $admin['server_url'];
        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('send/propReq');
    }

    public function propRequestAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['zones'] = $this->request->getPost('zone');
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['mailtitle'] = $this->request->getPost('mailtitle');
        $reqData['mailcontent'] = $this->request->getPost('mailcontent');
        $reqData['itemSelected'] = $this->request->getPost('itemSelected');
        $reqData['remark'] = $this->request->getPost('remark');

        //校验数据
        $validation = $this->paValidation;
        $validation->propSend();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        if($reqData['nickname'] == '@all'){
            $reqData['nickname'] = '';
        }

        $propSend = $this->getBussiness('Send')->propRequest($admin,$reqData);

        $this->functions->alert($propSend['msg']);
        return $this->dispatcher->forward(array(
            "controller" => "send",
            "action" => "propRequestView",
        ));
    }

    public function propDealViewAction(){
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_request_senditem_log';
        //获取总条数
        if(!isset($search) || $search == ''){
            $allcount = $this->db->query("select count(id) as allcount from $table");
        }else{
            $arr = [-1,0,1,2];
            if(in_array($search,$arr)){
                $allcount = $this->db->query("select count(id) as allcount from $table where `is_send`= $search");
            }else{
                $allcount = $this->db->query("select count(id) as allcount from $table `nickname`= $search");
            }
        }

        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`req_admin_name`,`deal_admin_name`,`nickname`,`item`,`server_name`,`diserver_id`,`remark`,`is_send` from $table order by created_at desc limit $page,$limit";
        }else{
            $arr = [-1,0,1,2];
            if(in_array($search,$arr)){
                $sql = "select `id`,`req_admin_name`,`deal_admin_name`,`nickname`,`item`,`server_name`,`diserver_id`,`remark`,`is_send` from $table where `is_send`= $search order by created_at desc limit $page,$limit";
            }else{
                $sql = "select `id`,`req_admin_name`,`deal_admin_name`,`nickname`,`item`,`server_name`,`diserver_id`,`remark`,`is_send` from $table where `nickname`= $search order by created_at desc limit $page,$limit";
            }
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "search=$search&";

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('send/propDeal');
    }

    public function propDealAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['id'] = $this->request->getQuery('id');
        $reqData['is_send'] = $this->request->getQuery('is_send');

        //校验数据
        $validation = $this->paValidation;
        $validation->propDeal();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        $send = $this->getBussiness('Send')->propDeal($admin,$reqData);//exit;
        $this->functions->alert($send['msg']);
        return $this->dispatcher->forward(array(
            "controller" => "send",
            "action" => "propDealView",
        ));
    }

    //定时发送道具
    public function propCrontabViewAction(){
        $admin = $this->dispatcher->getParam('admin');
        $diserver_id = $this->request->getQuery('diserver_id');

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

        $table = 'homepage_senditem_crontab';
        //获取总条数
        if(isset($diserver_id) && $diserver_id != ''){
            $allcount = $this->db->query("select count(id) as allcount from $table where diserver_id = $diserver_id");
        }else{
            $allcount = $this->db->query("select count(id) as allcount from $table");
        }
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(isset($diserver_id) && $diserver_id != ''){
            $sql = "select `id`,`admin_name`,`nickname`,`item`,`server_name`,`is_send`,`send_time`,`created_at` from $table where diserver_id = $diserver_id order by created_at desc limit $page,$limit";
        }else{
            $sql = "select `id`,`admin_name`,`nickname`,`item`,`server_name`,`is_send`,`send_time`,`created_at` from $table order by created_at desc limit $page,$limit";
        }
        $list = $this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        $data['admin'] = $admin;
        $data['server_list'] = $server_list;

        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->server_url = $admin['server_url'];
        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('send/propCrontab');
    }

    public function propCrontabAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['server_id'] = $this->request->getPost('server_id');
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['mailtitle'] = $this->request->getPost('mailtitle');
        $reqData['mailcontent'] = $this->request->getPost('mailcontent');
        $reqData['item'] = $this->request->getPost('item');
        $reqData['send_time'] = $this->request->getPost('send_time');

        //校验数据
        $validation = $this->paValidation;
        $validation->propCrontab();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        if($reqData['nickname'] == '@all'){
            $reqData['nickname'] = '';
        }

        $propCrontab = $this->getBussiness('Send')->propCrontab($admin,$reqData);

        $this->functions->alert($propCrontab['msg'],'/manager/prop/crontab');
    }

    public function propCrontabDealAction(){
        $reqData['id'] = $this->request->getQuery('id');
        $reqData['status'] = $this->request->getQuery('status');

        //校验数据
        $validation = $this->paValidation;
        $validation->propCrontabDeal();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
            exit;
        }

        switch ($reqData['status']){
            case '-1':  //删除
                //查询该数据是否存在
                $filed = 'id';
                $propCrontab = $this->getModel('SenditemCrontab')->getById($reqData['id'],$filed);
                if(!$propCrontab){
                    $this->functions->alert('已删除');exit;
                }

                //删除数据
                $del = $this->getModel('SenditemCrontab')->delById($reqData['id']);
                if(!$del){
                    $this->functions->alert('删除失败');exit;
                }
                $this->functions->alert('删除成功');exit;
                break;

            default:
                $this->functions->alert('数据参数传入缺失');exit;
                break;
        }
    }

}