<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class ZoneController extends ControllerBase
{
    public function listAction(){
        $zonelist = $this->getBussiness('GameApi')->getZoneList();
        if(!$zonelist){
            $zonelist = [];
        }

        //返回数据
        $data['allcount'] = sizeof($zonelist);  //总条数
        $data['page'] = '';
        $data['totalpage'] = '';
        $data['search'] = '';

        $this->view->list = $zonelist;
        $this->view->data = $data;
        $this->view->pick('zone/list');
    }

    public function exchangeAction(){
        $reqData['exchange_code'] = $this->request->getPost('exchange_code');
        $reqData['zones'] = $this->request->getPost('zones');
        $reqData['user_name'] = $this->request->getPost('nickname');

        //校验数据
        $validation = $this->paValidation;
        $validation->exchange();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $this->result['status'] = -1;
            $this->result['msg'] = $message = $messages[0]->getMessage();
            return json_encode($this->result);
        }

        $exchange = $this->getBussiness('Exchange')->exchange($reqData);
        return json_encode($exchange);
    }

}