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

        $this->view->data = $data;
        $this->view->pick('user/distalk');
    }

    public function disableAction(){
        $reqData['zones'] = $this->request->getPost('zones');
        $reqData['user'] = $this->request->getPost('user');
        $reqData['t'] = $this->request->getPost('t');

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

        //从redis获取
        $key = 'Game_Nickname';
        $user_id = $this->redis->hGet($key,$reqData['nickname']);
        if(!$user_id){
            $this->functions->alert('该用户不存在');
            exit;
        }
        $userInfo = $this->getBussiness('User')->getUserInfo($user_id,$reqData);

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
        $reqData['zone'] = $this->request->getPost('zone');
        $reqData['nickname'] = $this->request->getPost('nickname');
        $reqData['t'] = $this->request->getPost('t');

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

        $this->functions->alert('禁言成功');

        return $this->dispatcher->forward(array(
            "controller" => "user",
            "action" => "disableView",
        ));
    }

}