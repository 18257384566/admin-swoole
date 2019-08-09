<?php

namespace App\Plugins;

use App\Bussiness\RedisCache;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class AclPlugin extends Injectable
{
    public function beforeExecuteRoute(Event $event , Dispatcher $dispatcher)
    {
        $role = 'visitor';

        //判断管理员是否过期
        $checkLogin = $this->checkLogin();
        if($checkLogin){
            $role = 'auth';
        }

        //对此控制器方法是否有访问权限
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();
        $resource = $controller."::".$action;

        if($this->allow($role,$resource)){
            return true;
        }

        return $this->dispatcher->forward(array(
                "controller" => "index",
                "action" => "login",
            ));
        exit;
    }

    public function allow($role = "visitor",$resource)
    {
        $acl = array(
            'visitor' => array(
                'index::login',
                'index::doLogin',


                //test
                'test::addtable',
            ),
            'auth' => array(
                'index::index',
                'admin::getList',
                'admin::updateStatus',
                'admin::add',
                'admin::addView',
                'index::signOut',
                'admin::addAdmin',
                'admin::adminLog'

            ),

        );

        if(in_array($resource,$acl[$role])){
            return true;
        }
        return false;
    }

    public function checkLogin()
    {
        //判断管理员是否退出
        $adminSession = $this->session->get('backend');
        if(!$adminSession){
            return false;
        }

        //判断管理员是否过期
        if(isset($_SESSION['expiretime'])) {
            if($_SESSION['expiretime'] < time()) {
                return false;
            }
            //更新过期时间
            $_SESSION['expiretime'] = time() + $this->config->lifetime['login'];
        }

        $adminSession = $this->session->get('backend');
        $this->dispatcher->setParam('admin',$adminSession);

        return true;

    }

}