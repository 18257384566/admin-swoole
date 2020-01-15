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

}