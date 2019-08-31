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

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('send/prop');
    }

    public function noticeViewAction(){
        $admin = $this->dispatcher->getParam('admin');

        $this->view->server_url = $admin['server_url'];
        $this->view->pick('send/notice');
    }

}