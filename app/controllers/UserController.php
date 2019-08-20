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
        $this->view->pick('user/dailyLogin');
    }

    public function retainAction(){
        $this->view->pick('user/retain');
    }

    public function loginCountAction(){
        $this->view->pick('user/loginCount');
    }

    public function onlineAction(){
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

        $this->view->list = $propList;
        $this->view->data = [];
        $this->view->pick('user/propList');
    }

}