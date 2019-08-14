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
        $url = $this->config['gameUrl'].'/gm/getzonelist';
        try{
            $zonelist = $this->functions->http_request_code($url, 'GET');
            if(!$zonelist){
                $this->functions->alert('获取失败','/admin/index');
                exit;
            }
        }catch (\Exception $e){
            $this->functions->alert('获取失败','/admin/index');
            exit;
        }

        if(!$zonelist['success']){
            $this->functions->alert('获取失败','/admin/index');
            exit;
        }

        unset($zonelist['success']);

//
//        $limit = 10;
//        $page = $this->request->get('page');
//
//        if(!$page){
//            $page=1;
//        }
//        $page = ($page - 1) * $limit;
//
//        $table = 'homepage_exchange';
//
//        $search = $this->request->get('search');
//        if(isset($search) && $search != ''){
//            //获取总条数
//            $allcount = $this->db->query("select count(id) as allcount from $table  where exchange_code like '$search%' or uid like '$search%' or user_name like '$search%'");
//            $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
//            $allcount = $allcount->fetch();
//
//            //获取当页
//            $sql = "select id,exchange_code,card_no,is_used,uid,used_time,user_name from $table where exchange_code like '$search%' or uid like '$search%' or user_name like '$search%' order by created_at desc limit $page,$limit";
//            $list=$this->db->query($sql);
//            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
//            $list = $list->fetchAll();
//        }else{
//            //获取总条数
//            $allcount = $this->db->query("select count(id) as allcount from $table");
//            $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
//            $allcount = $allcount->fetch();
//
//            //获取当页
//            $sql = "select id,exchange_code,card_no,is_used,uid,used_time,user_name from $table order by created_at desc limit $page,$limit";
//            $list=$this->db->query($sql);
//            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
//            $list = $list->fetchAll();
//        }
//
//        //返回数据
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