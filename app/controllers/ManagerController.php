<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class ManagerController extends ControllerBase
{

    public function noticeListAction(){
        $limit = 10;
        $page = $this->request->get('page');
        $channel = $this->request->get('channel');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_notice';
        //获取总条数
        if(!isset($channel) || $channel == ''){
            $allcount = $this->db->query("select count(id) as allcount from $table");
        }else{
            $allcount = $this->db->query("select count(id) as allcount from $table where channel = '$channel'");
        }
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($channel) || $channel == ''){
            $sql = "select id,notice,created_at,channel,remark,start_time,admin_name from $table order by created_at desc limit $page,$limit";
        }else{
            $sql = "select id,notice,created_at,channel,remark,start_time,admin_name from $table where channel = '$channel' order by created_at desc limit $page,$limit";
        }

        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        foreach ($list as &$v){
            $v['notice'] = str_replace('\\r\\n', '<br>', $v['notice']);
            $v['notice'] = str_replace('\\n', '<br>', $v['notice']);
            $v['notice'] = str_replace('\\r', '<p>', $v['notice']);


        }

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = 'channel='.$channel.'&';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('manager/noticeList');
    }

    public function noticeAddAction(){
        $admin = $this->dispatcher->getParam('admin');

        $reqData['channel'] = $this->request->getPost('channel');
        $reqData['notice'] = $this->request->getPost('notice');
        $reqData['remark'] = $this->request->getPost('remark');
        $reqData['start_time'] = $this->request->getPost('start_time');

        //校验数据
        $validation = $this->paValidation;
        $validation->noticeAdd();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->functions->alert($message);
        }

        $reqData['start_time'] = strtotime($reqData['start_time']);

        $reqData['admin_no'] = $admin['admin_no'];
        $reqData['admin_name'] = $admin['account'];
        $add = $this->getModel('Notice')->add($reqData);
        if(!$add){
            $this->functions->alert('添加失败');
        }

        $this->functions->alert('添加成功');
    }

    public function noticeDealAction(){
        $reqData['status'] = $this->request->getQuery('status');
        $id = $this->request->getQuery('id');
        if(!isset($reqData['status']) || !isset($id)){
            $this->functions->alert('参数传输错误');
        }

        $update = $this->getModel('Notice')->updateById($id,$reqData);
        if(!$update){

            $msg = '修改失败';
            switch ($reqData['status']){
                case '-1':
                    $msg = '删除失败';
                    break;
            }

        }else{

            $msg = '修改成功';
            switch ($reqData['status']){
                case '-1':
                    $msg = '删除成功';
                    break;
            }

        }

        $this->functions->alert($msg);
    }

    public function noticeApiAction(){
        $reqData['channel'] = $this->request->getQuery('channel');
        if(!isset($reqData['channel']) || $reqData['channel'] == ''){
            $reqData['channel'] = 'pt';
        }

        $reqData['channel'] = strtolower($reqData['channel']);

        //校验数据
        $validation = $this->paValidation;
        $validation->noticeApi();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();

            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $filed = 'notice';
        $notice = $this->getModel('Notice')->getByChannel($reqData['channel'],$filed);
        if(!$notice){
            $this->result['status'] = 1;
            $this->result['msg'] = '';
            $this->result['data']['notice'] = '';
            return json_encode($this->result);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '';
        $this->result['data']['notice'] = $notice['notice'].PHP_EOL;
        return json_encode($this->result,JSON_UNESCAPED_UNICODE);
    }

}