<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class SwooleController extends ControllerBase
{

    public function chatAction(){
        //权限
        $admin = $this->dispatcher->getParam('admin');

        $this->view->admin = $admin;
        $this->view->pick('admin/chat');
    }

    public function chatSendAction(){
        //推送消息
        $data['name'] = '小明';
        $data['content'] = 'hi';
        echo '<pre>';
        var_dump($_POST['http_server']);
        $_POST['http_server']->push(2,'push-xsy');
//        foreach ($_POST['http_server']->ports->connections as $fd){
////            var_dump($fd);
//            $_POST['http_server']->push($fd, json_encode($data));
//        }

        $this->result['status'] = 1;
        $this->result['msg'] = 'ok';
        return json_encode($this->result);
    }

}