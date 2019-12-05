<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class UserController extends ControllerBase
{
    public function dailyLoginAction(){
        $admin = $this->dispatcher->getParam('admin');

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('user/dailyLogin');
    }

    public function retainAction(){
        $admin = $this->dispatcher->getParam('admin');

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('user/retain');
    }

    public function loginCountAction(){
        $admin = $this->dispatcher->getParam('admin');

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('user/loginCount');
    }

    public function onlineAction(){
        $admin = $this->dispatcher->getParam('admin');

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('user/online');
    }

    public function onlineQueryAction(){
        $reqData['zones'] = $this->request->getPost('zone');
        $reqData['t1'] = $this->request->getPost('sDate');

        //校验数据
        $validation = $this->paValidation;
        $validation->onlineQuery();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $reqData['t1'] = strtotime($reqData['t1']);
        $reqData['t2'] = $reqData['t1'] + 86400;

        //查询实时在线
        $reqData['channels'] = '';
        $reqData['type'] = 'UserOnline';
        $online = $this->getBussiness('GameApi')->analyze($reqData);

        $onlineList = [];
        if(isset($online['TimeS']) && isset($online['CountS'])){
            //拼接参数
            foreach ($online['TimeS'] as $k => $v){
                $onlineList[$k]['time'] = $v;
                $onlineList[$k]['count'] = $online['CountS'][$k];
            }
        }

        $data['onlineList'] = $onlineList;
        $this->view->data = $data;
        $this->view->pick('user/online');
    }

    public function getShipInfoViewAction(){
        $this->view->pick('user/shipinfo');
    }

    public function getShipInfoAction(){
        $reqData['nickname'] = $this->request->getPost('nickname');

        //校验数据
        $validation = $this->paValidation;
        $validation->getShipInfo();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $usershipinfo = $this->getBussiness('GameApi')->getusershipinfo($reqData);
        if(!$usershipinfo){
            $this->functions->alert('获取失败，昵称不存在');
        }

        $data['nickname'] = $reqData['nickname'];
        $data['allcount'] = 0;
        $data['page'] = 0;
        $data['totalpage'] = 0;
        $data['search'] = '';

        $this->view->list = $usershipinfo;
        $this->view->data = $data;
        $this->view->pick('user/shipInfo');
    }

    public function propListAction(){
        $propList = $this->getBussiness('GameApi')->getItemList();
        if(!isset($propList) || $propList == ''){
            $propList = [];
        }

        $this->view->list = $propList;
        $this->view->data = [];
        $this->view->pick('user/propList');
    }

    public function propListExcelAction(){
        $propList = $this->getBussiness('GameApi')->getItemList();
        if(!$propList){
            $this->functions->alert('暂无数据');
        }

        $title = ['id','道具名'];
        foreach ($propList as $k => $v){
            $excelData[] = [$k,$v];
        }


        //导表
        header('Content-Type: application/vnd.ms-excel'); //设置文件类型   也可以将 vnd.ms-excel' 改成xml（导出xml文件）
        header('Content-Disposition: attachment;filename="道具表-'.time().'.xls"'); //设置导出的excel的名字
        header('Cache-Control: max-age=0');
        set_time_limit (0);

        echo iconv("utf-8","gbk","id\t道具名\n");  //  \t是制表符 \n是换行符
        foreach ($propList as $k => $v){   //$arr 是所要导出的数
            echo iconv("utf-8","gbk","{$k}\t{$v}\n");
        }
        exit;
    }

    public function disableViewAction(){
        $admin = $this->dispatcher->getParam('admin');
        //根据服务器id查找区服名
        $filed = 'diserver_name,diserver_id';
        $data['server'] = $this->getModel('Server')->getById($admin['server_id'],$filed);
        if(!$data['server']){
            $data['server']['diserver_name'] = '';
            $data['server']['diserver_id'] = '';
        }

        $diserver_id = $data['server']['diserver_id'];

        //查询记录
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_disable_log';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`admin_name`,`nickname`,`server_name`,`end_time` from $table where `diserver_id` = $diserver_id order by created_at desc limit $page,$limit";
        }else{
            $sql = "select `id`,`admin_name`,`nickname`,`server_name`,`end_time` from $table where `diserver_id` = $diserver_id order by created_at desc limit $page,$limit";
        }

        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = 'server_name='.$search.'&';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('user/disable');
    }

    public function distalkViewAction(){
        $admin = $this->dispatcher->getParam('admin');
        //根据服务器id查找区服名
        $filed = 'diserver_name,diserver_id';
        $data['server'] = $this->getModel('Server')->getById($admin['server_id'],$filed);
        if(!$data['server']){
            $data['server']['diserver_name'] = '';
            $data['server']['diserver_id'] = '';
        }

        $diserver_id = $data['server']['diserver_id'];

        //查询记录
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_distalk_log';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `id`,`admin_name`,`nickname`,`server_name`,`end_time` from $table where `diserver_id` = $diserver_id order by created_at desc limit $page,$limit";
        }else{
            $sql = "select `id`,`admin_name`,`nickname`,`server_name`,`end_time` from $table where `diserver_id` = $diserver_id order by created_at desc limit $page,$limit";
        }

        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = 'server_name='.$search.'&';

        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('user/distalk');
    }

    public function disableAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['zones'] = $this->request->getPost('zones');
        $reqData['user'] = $this->request->getPost('user');
        $reqData['t'] = $this->request->getPost('t');
        $reqData['end_time'] = $this->request->getPost('t');

        //校验数据
        $validation = $this->paValidation;
        $validation->disable();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $reqData['t'] = strtotime($reqData['t']);

        //发送封号请求
        $disable = $this->getBussiness('GameApi')->ban($reqData);
        if(!$disable){
            $this->functions->alert('封禁失败');
        }

        //添加封禁记录
        $log = [];
        $log['admin_name'] = $admin['account'];
        $log['admin_no'] = $admin['admin_no'];
        $log['nickname'] = $reqData['user'];
        $log['server_name'] = $admin['server_name'];
        $log['diserver_id'] = $reqData['zones'];
        $log['server_url'] = $admin['server_url'];
        $log['end_time'] = $reqData['end_time'];
        $this->getModel('Disable')->add($log);

        $this->functions->alert('封禁成功');

        return $this->dispatcher->forward(array(
            "controller" => "user",
            "action" => "disableView",
        ));
    }

    public function infoViewAction(){
        $this->view->pick('user/info');
    }

    public function infoAction(){
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['type'] = $this->request->getPost('type');

        //校验数据
        $validation = $this->paValidation;
        $validation->userInfo();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        //从api获取
//        $userInfo = $this->getBussiness('GameApi')->getUserInfo($reqData);
//        if(!$userInfo){
//            $this->functions->alert('查询失败','/user/shipInfo');exit;
//        }

        //从redis获取1
//        $key = 'Game_Nickname';
//        $user_id = $this->redis->hGet($key,$reqData['nickname']);
//        if(!$user_id){
//            $this->functions->alert('该用户不存在');
//            exit;
//        }
//        $userInfo = $this->getBussiness('User')->getUserInfo($user_id,$reqData);

        //从redis获取2
        $userInfo = $this->getBussiness('Redis')->getUserInfo($reqData['nickname'],$reqData['type']);

        $data['type'] = $reqData['type'];
        $data['list'] = $userInfo;
        $data['nickname'] = $reqData['nickname'];

        if($reqData['type'] == 'construction'){
            //查询船建筑表
            $filed = 'tid,name';
            $construction = $this->getModel('Construction')->getList($filed);
            if(!$construction){
                $this->functions->alert('查询失败','/user/shipInfo');exit;
            }

            $construction = $this->functions->arraykey($construction,'tid');

            foreach ($data['list'] as &$v){
                $v['name'] = $construction[$v['Tid']]['name'];
            }
        }

        $this->view->data = $data;
        $this->view->pick('user/info');
    }

    public function notalkAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['zone'] = $this->request->getPost('zone');
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['t'] = $this->request->getPost('t');
        $reqData['end_time'] = $this->request->getPost('t');

        //校验数据
        $validation = $this->paValidation;
        $validation->notalk();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $reqData['t'] = strtotime($reqData['t']);

        //发送封号请求
        $disable = $this->getBussiness('GameApi')->talkban($reqData);
        if(!$disable){
            $this->functions->alert('禁言失败');
        }

        //添加封禁记录
        $log = [];
        $log['admin_name'] = $admin['account'];
        $log['admin_no'] = $admin['admin_no'];
        $log['nickname'] = $reqData['nickname'];
        $log['server_name'] = $admin['server_name'];
        $log['diserver_id'] = $reqData['zone'];
        $log['server_url'] = $admin['server_url'];
        $log['end_time'] = $reqData['end_time'];
        $this->getModel('Distalk')->add($log);

        $this->functions->alert('禁言成功');

        return $this->dispatcher->forward(array(
            "controller" => "user",
            "action" => "disableView",
        ));
    }

    //注册记录
    public function registerViewAction(){
        $start_time = $this->request->getQuery('start_time');
        $end_time = $this->request->getQuery('end_time');

        if(empty($start_time)){
            $start_time = '1997-01-01';
        }

        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        //获取订单列表
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->getQuery('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_user';
        //获取总条数
        if(!isset($search) || $search == ''){
            $sql = "select count(id) as allcount from $table where `time` >= $start_time and `time` < $end_time";
        }else{
            $server_id = 'zone'.$search;
            $sql = "select count(id) as allcount from $table where `time` >= $start_time and `time` < $end_time and `server_id` = '$server_id'";
        }
        $allcount=$this->db->query($sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `time`,`user_id`,`channel`,`server_id`,`register_ip`,`country_code` from $table where `time` >= $start_time and `time` < $end_time order by `date` desc limit $page,$limit";
        }else{
            $server_id = 'zone'.$search;
            $sql = "select `time`,`user_id`,`channel`,`server_id`,`register_ip`,`country_code` from $table where `time` >= $start_time and `time` < $end_time and `server_id` = '$server_id' order by `date` desc limit $page,$limit";
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();


        //获取服务器
        $sql = "select `server_name`,`diserver_id`,`diserver_name` from homepage_server order by created_at desc limit $page,$limit";
        $server=$this->db->query($sql);
        $server->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $server = $server->fetchAll();

        $data['server'] = $server;
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "search=$search&start_time=".date('Y-m-d',$start_time)."&end_time=".date('Y-m-d',$end_time)."&";

        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('user/register');
    }

    public function registerImportAction(){
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
        for ($i = 0; $i < $len_result; $i++) { //循环获取各字段值
            $json = mb_convert_encoding($result[$i][0], "UTF-8", "auto");

            //判断数据是否为空
            if(!isset($json) || $json == ''){
                continue;
            }

            $data = json_decode($json,true);
            if(!isset($data['properties'])){
                continue;
            }

            //判断该订单是否已经存在
            $isset = $this->getModel('User')->getByUserId($data['properties']['user_id'],$filed='id');
            if($isset){
                continue;
            }

            $time = strtotime($data['#time']);
            $date = date('Y-m-d',$time);
            $month = date('Y-m',$time);

            if(!isset($data['properties']['device_id'])){
                $data['properties']['device_id'] = 0;
            }
            //存入数据库
            $sql = "insert into homepage_user(`account_id`,`time`,`date`,`month`,`user_id`,`device_id`,`channel`,`server_id`,`register_ip`,`idfa_imei`,`phone_os`,`country_code`,`cmgeSDK_id`,`extend_id`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $params = array(
                $data['properties']['account_id'],
                $time,
                $date,
                $month,
                $data['properties']['user_id'],
                $data['properties']['device_id'],
                $data['properties']['channel'],
                $data['properties']['server_id'],
                $data['properties']['register_ip'],
                $data['properties']['idfa_imei'],
                $data['properties']['phone_os'],
                $data['properties']['country_code'],
                $data['properties']['cmgeSDK_id'],
                $data['properties']['extend_id'],
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/user/registerView');
    }


    //登陆记录
    public function loginViewAction(){
        $start_time = $this->request->getQuery('start_time');
        $end_time = $this->request->getQuery('end_time');

        if(empty($start_time)){
            $start_time = '1997-01-01';
        }

        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        //获取订单列表
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->getQuery('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_login_log';
        //获取总条数
        if(!isset($search) || $search == ''){
            $sql = "select count(id) as allcount from $table where `time` >= $start_time and `time` < $end_time";
        }else{
            $server_id = 'zone'.$search;
            $sql = "select count(id) as allcount from $table where `time` >= $start_time and `time` < $end_time and `server_id` = '$server_id'";
        }
        $allcount=$this->db->query($sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `time`,`user_id`,`channel`,`server_id`,`login_ip`,`vip_level`,`level` from $table where `time` >= $start_time and `time` < $end_time order by `date` desc limit $page,$limit";
        }else{
            $server_id = 'zone'.$search;
            $sql = "select `time`,`user_id`,`channel`,`server_id`,`login_ip`,`vip_level`,`level` from $table where `time` >= $start_time and `time` < $end_time and `server_id` = '$server_id' order by `date` desc limit $page,$limit";
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();


        //获取服务器
        $sql = "select `server_name`,`diserver_id`,`diserver_name` from homepage_server order by created_at desc limit $page,$limit";
        $server=$this->db->query($sql);
        $server->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $server = $server->fetchAll();

        $data['server'] = $server;
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "search=$search&start_time=".date('Y-m-d',$start_time)."&end_time=".date('Y-m-d',$end_time)."&";

        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('user/loginLog');
    }

    public function loginImportAction(){
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
        for ($i = 0; $i < $len_result; $i++) { //循环获取各字段值
            $json = mb_convert_encoding($result[$i][0], "UTF-8", "auto");

            //判断数据是否为空
            if(!isset($json) || $json == ''){
                continue;
            }

            $data = json_decode($json,true);
            if(!isset($data['properties'])){
                continue;
            }

            $time = strtotime($data['#time']);
            $date = date('Y-m-d',$time);

            //判断该记录是否存在
            $isset = $this->getModel('LoginLog')->getByUserIdTime($data['properties']['user_id'],$time,$filed='id');
            if($isset){
                continue;
            }

            if(!isset($data['properties']['device_id'])){
                $data['properties']['device_id'] = 0;
            }
            //存入数据库
            $sql = "insert into homepage_login_log(`account_id`,`time`,`date`,`user_id`,`device_id`,`channel`,`server_id`,`login_ip`,`vip_level`,`level`) VALUES (?,?,?,?,?,?,?,?,?,?)";
            $params = array(
                $data['properties']['account_id'],
                $time,
                $date,
                $data['properties']['user_id'],
                $data['properties']['device_id'],
                $data['properties']['channel'],
                $data['properties']['server_id'],
                $data['properties']['login_ip'],
                $data['properties']['vip_level'],
                $data['properties']['level'],
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/user/loginView');
    }

}