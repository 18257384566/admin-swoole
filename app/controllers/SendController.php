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
//        $admin = $this->dispatcher->getParam('admin');
//        $this->view->adminName = $admin['account'];
//
//        $this->view->permission = '1';
        $this->view->pick('send/prop');
    }

    public function noticeViewAction(){
        $this->view->pick('send/notice');
    }

}