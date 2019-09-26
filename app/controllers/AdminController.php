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


    //服务器
    public function serverListAction(){
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('type');
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
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`server_name`,`url`,`created_at`,`type`,`diserver_id`,`diserver_name` from $table order by created_at desc limit $page,$limit";
        }else{
            $sql = "select `id`,`server_name`,`url`,`created_at`,`type`,`diserver_id`,`diserver_name` from $table where `type`= $search order by created_at desc limit $page,$limit";
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "type=$search&";

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/serverList');
    }

    public function serverAddAction(){
        $reqData['server_name'] = $this->request->getPost('server_name');
        $reqData['url'] = $this->request->getPost('url');
        $reqData['type'] = $this->request->getPost('type');
        $reqData['diserver_id'] = $this->request->getPost('diserver_id');
        $reqData['diserver_name'] = $this->request->getPost('diserver_name');

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

    public function serverUpdateViewAction(){
        $id = $this->request->getQuery('id');
        if(!isset($id) || $id == ''){
            $this->function->alert('参数丢失');
            exit;
        }

        $filed = 'server_name,url,type,id,diserver_id,diserver_name';
        $server = $this->getModel('Server')->getById($id,$filed);
        if(!$server){
            $this->function->alert('该服务器不存在或已被删除');
            exit;
        }

        $data['server'] = $server;
        $this->view->data = $data;
        $this->view->pick('admin/serverUpdate');
    }

    public function serverUpdateAction(){
        $reqData['server_name'] = $this->request->getPost('server_name');
        $reqData['url'] = $this->request->getPost('url');
        $reqData['type'] = $this->request->getPost('type');
        $reqData['diserver_id'] = $this->request->getPost('diserver_id');
        $reqData['diserver_name'] = $this->request->getPost('diserver_name');
        $id = $this->request->getPost('id');

        if(!isset($id) || $id == ''){
            $this->functions->alert('参数丢失','/admin/server/list');
            exit;
        }

        //校验数据
        $validation = $this->paValidation;
        $validation->serverAdd();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $update = $this->getModel('Server')->updateById($id,$reqData);
        if(!$update){
            $this->functions->alert('修改失败','/admin/server/list');
            exit;
        }
        $this->functions->alert('修改成功','/admin/server/list');
        exit;
    }

    public function serverRedisViewAction(){
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('type');
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
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`server_name`,`url`,`created_at`,`type`,`diserver_id`,`diserver_name`,`redis_url` from $table order by created_at desc limit $page,$limit";
        }else{
            $sql = "select `id`,`server_name`,`url`,`created_at`,`type`,`diserver_id`,`diserver_name`,`redis_url` from $table where `type`= $search order by created_at desc limit $page,$limit";
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "type=$search&";

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/serverRedis');
    }

    public function serverRedisUpdateViewAction(){
        $id = $this->request->getQuery('id');
        if(!isset($id) || $id == ''){
            $this->function->alert('参数丢失');
            exit;
        }

        $filed = 'server_name,url,type,id,diserver_id,diserver_name,redis_url';
        $server = $this->getModel('Server')->getById($id,$filed);
        if(!$server){
            $this->function->alert('该服务器不存在或已被删除');
            exit;
        }

        $data['server'] = $server;
        $this->view->data = $data;
        $this->view->pick('admin/serverRedisUpdate');
    }

    public function serverRedisUpdateAction(){
        $reqData['server_name'] = $this->request->getPost('server_name');
        $reqData['url'] = $this->request->getPost('url');
        $reqData['type'] = $this->request->getPost('type');
        $reqData['diserver_id'] = $this->request->getPost('diserver_id');
        $reqData['diserver_name'] = $this->request->getPost('diserver_name');
        $reqData['redis_url'] = $this->request->getPost('redis_url');
        $id = $this->request->getPost('id');

        if(!isset($id) || $id == ''){
            $this->functions->alert('参数丢失','/admin/server/list');
            exit;
        }

        //校验数据
        $validation = $this->paValidation;
        $validation->serverAdd();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $update = $this->getModel('Server')->updateById($id,$reqData);
        if(!$update){
            $this->functions->alert('修改失败','/admin/server/list');
            exit;
        }
        $this->functions->alert('修改成功','/admin/server/redis');
        exit;
    }


    //区服
    public function diserverListAction(){
        //获取区服列表
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('server_name');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_diserver';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`server_name`,`diserver_id`,`diserver_name`,`created_at` from $table order by created_at desc limit $page,$limit";
        }else{
            $sql = "select `id`,`server_name`,`diserver_id`,`diserver_name`,`created_at` from $table where `server_name` = '$search' order by created_at desc limit $page,$limit";
        }

        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = 'server_name='.$search.'&';

        //获取服务器列表
        $filed = 'id,server_name';
        $data['server_list'] = $this->getModel('Server')->getList($filed);
        if(!$data['server_list']){
            $data['server_list'] = [];
        }

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('admin/diserverList');
    }

    public function diserverAddAction(){
        $reqData['server_id'] = $this->request->getPost('server_id');
        $reqData['diserver_id'] = $this->request->getPost('diserver_id');
        $reqData['diserver_name'] = $this->request->getPost('diserver_name');

        //校验数据
        $validation = $this->paValidation;
        $validation->diserverAdd();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $add = $this->getBussiness('Server')->addDiserver($reqData);
        $this->functions->alert($add['msg'],'/admin/diserver/list');
    }

    public function diserverDelAction(){
        $reqData['id'] = $this->request->getQuery('id');
        if(!isset($reqData['id']) || $reqData['id'] == ''){
            $this->functions->alert('信息丢失，修改失败');
        }

        $del = $this->getModel('Diserver')->delDiserver($reqData['id']);
        if(!$del){
            $this->functions->alert('删除失败');
        }

        $this->functions->alert('删除成功');
    }

    public function getzonelistAction(){
        $admin = $this->dispatcher->getParam('admin');

        //根据服务器id查找区服名
        $filed = 'diserver_name,diserver_id';
        $diserver = $this->getModel('Server')->getById($admin['server_id'],$filed);
        if(!$diserver){
            $diserver = [];
        }

        $i = 1;
        $zonelist = [];
        $zonelist[$i]['ServerName'] = $diserver['diserver_name'];
        $zonelist[$i]['ServerStatus'] = (int)$diserver['diserver_id'];
        $zonelist['success'] = true;

        return json_encode($zonelist);
    }


    //渠道
    public function channelListAction(){
//        $this->view->list = $list;
//        $this->view->data = $data;
        $this->view->pick('admin/channelList');
    }

    public function summaryAction(){
        $admin = $this->dispatcher->getParam('admin');

        //获取创角色统计
        $user_count = $this->getBussiness('Redis')->userCount();

        $data['user_count'] = $user_count;
        $data['admin'] = $admin;

        $this->view->data = $data;
        $this->view->pick('admin/summary');

    }

    public function orderAddViewAction(){
        //查询最新插入数据的时间
        $filed = 'created_at';
        $order = $this->getModel('Order')->getLast($filed);
        if(!$order){
            $order['created_at'] = 0;
        }

        //获取订单列表
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_order';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `UserId`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId` from $table order by CreateTime desc limit $page,$limit";
        }else{
            $sql = "select `UserId`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId` from $table where `orderId` = '$search' or `NickName` = '$search' order by CreateTime desc limit $page,$limit";
        }

        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = 'server_name='.$search.'&';

        $data['order'] = $order;
        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('admin/orderAdd');
    }

    public function orderAddAction(){
        //判断上传文件是否合法
        $filename = $_FILES['file']['tmp_name'];
        $name = strstr( $_FILES['file']['name'], '.');
        if($name != '.csv' && $name != '.tsv'){
            $this->functions->alert('导入文件格式只能为csv或者tsv');
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
            $json = mb_convert_encoding($result[$i][0], "UTF-8", "auto");

            if($json == 'orderdata'){
                continue;
            }

            //判断数据是否为空
            if(!isset($json) || $json == ''){
                continue;
            }

            $data = json_decode($json,true);
            if(!isset($data['Status'])){
                continue;
            }

            //判断该订单是否已经存在
            $isset = $this->getModel('Order')->getByOrderId($data['Id'],$filed='id');
            if($isset){
                continue;
            }

            $Items = '';
            if(isset($data['Items']) && $data['Items'] != ''){
                foreach ($data['Items'] as $v){
                    $Items .= $v['ItemId'].','.$v['ItemQuantity'].';';
                }
                $Items = rtrim($Items,';');
            }

            //存入数据库
            $sql = "insert into homepage_order(`UserId`,`Complete`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId`,`created_at`,`update_at`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $params = array(
                $data['UserId'],
                $data['Complete'],
                $data['Channel'],
                $data['Amount'],
                $data['CreateTime'],
                $Items,
                $data['Id'],
                $data['NickName'],
                $data['GoodsId'],
                $time,
                $time
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/admin/orderadd/excle');
    }

}