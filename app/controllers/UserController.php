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

}