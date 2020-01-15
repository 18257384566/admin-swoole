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
            $role = $checkLogin['role'];
        }

        //对此控制器方法是否有访问权限
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();
        $resource = $controller."::".$action;

        //过滤api接口
        if ($resource == 'manager::noticeApi' || $resource == 'obonus::use' || $resource == 'exchange::exchange' || $resource == 'transfer::transfeStation'){
            return true;
        }

        if($this->allow($role,$resource)){
            return true;
        }

        if($checkLogin){
            $this->functions->alert('无此权限，请联系管理员','/admin/index'); //exit;

            return $this->dispatcher->forward(array(
                "controller" => "index",
                "action" => "index",
            ));

            exit;
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
                'exchange::exchange',


            ),
            'supper' => array(
                'index::index',
                'admin::getList',
                'admin::updateStatus',
                'admin::update',
                'admin::updateView',
                'admin::add',
                'admin::addView',
                'index::signOut',
                'admin::addAdmin',
                'admin::adminLog',
                'exchange::addView',
                'exchange::addExchange',
                'exchange::list',
                'zone::list',
                'send::propView',
                'send::prop',
                'send::propServerView',
                'send::propServer',
                'send::noticeView',
                'send::tableSendView',
                'send::sendTableExcel',
                'send::tableSend',
                'send::tableAddView',
                'send::tableAdd',
                'user::dailyLogin',
                'user::retain',
                'user::loginCount',
                'user::online',
                'user::onlineQuery',
                'exchange::cardAddView',
                'user::propList',
                'user::propListExcel',
                'user::disableView',
                'user::disable',
                'user::infoView',
                'user::info',
                'user::getShipInfoView',
                'user::notalk',
                'user::registerView',
                'user::registerImport',
                'user::loginView',
                'user::loginImport',
                'manager::noticeList',
                'manager::noticeAdd',
                'manager::noticeDeal',
                'admin::serverList',
                'admin::serverAdd',
                'admin::serverDel',
                'admin::serverUpdateView',
                'admin::serverUpdate',
                'admin::serverRedis',
                'admin::serverRedisView',
                'admin::serverRedisUpdateView',
                'admin::serverRedisUpdate',
                'admin::summary',
                'data::update',
                'admin::diserverList',
                'admin::diserverAdd',
                'admin::diserverDel',
                'admin::getzonelist',
                'user::distalkView',
                'admin::channelList',
                'obonus::addView',
                'obonus::addObonus',
                'obonus::list',
                'obonus::use',
                'order::orderAddView',
                'order::orderAdd',
                'send::propRequestView',
                'send::propRequest',
                'send::propDealView',
                'send::propDeal',
                'send::propCrontabView',
                'send::propCrontab',
                'send::propCrontabDeal',
                'send::propPlanView',
                'send::propPlan',
                'send::planDeal',
                'order::orderLogAdd',
                'order::rechargeView',
                'order::rechargeImport',
                'order::additemDiamondView',
                'order::additemDiamondAdd',
                'analy::registerView',
                'analy::registerQuery',
                'analy::loginView',
                'analy::loginQuery',
                'swoole::chat',
                'swoole::chatSend',
            ),

            'developers' => array(  //开发商
                'index::login',
                'index::doLogin',
                'index::signOut',
                'index::index',
            ),

            'operators' => array(   //运维
                'index::login',
                'index::doLogin',
                'index::signOut',
                'index::index',

                'admin::serverList',
                'admin::serverAdd',
                'admin::serverDel',
                'admin::serverUpdateView',
                'admin::serverUpdate',
                'admin::summary',
                'exchange::addView',
                'exchange::addExchange',
                'exchange::list',
                'exchange::cardAddView',
                'send::propRequestView',
                'send::propRequest',
                'manager::noticeList',
                'manager::noticeAdd',
                'manager::noticeDeal',
                'user::dailyLogin',
                'user::retain',
                'user::loginCount',
                'user::online',
                'user::onlineQuery',
                'user::propList',
                'user::propListExcel',
                'user::disableView',
                'user::disable',
                'user::distalkView',
                'user::notalk',
                'user::infoView',
                'user::info',
                'obonus::addView',
                'obonus::addObonus',
                'obonus::list',
                'obonus::use',
                'order::orderAddView',
                'order::orderAdd',

            ),

            'service' => array(     //普通账户
                'index::login',
                'index::doLogin',
                'index::signOut',
                'index::index',

                'send::propRequestView',
                'send::propRequest',
                'manager::noticeList',
                'manager::noticeAdd',
                'manager::noticeDeal',
                'user::dailyLogin',
                'user::retain',
                'user::loginCount',
                'user::online',
                'user::onlineQuery',
                'user::propList',
                'user::propListExcel',
                'user::disableView',
                'user::disable',
                'user::distalkView',
                'user::notalk',
                'user::infoView',
                'user::info',
            ),

            'commissioner' => array(    //专员
                'index::login',
                'index::doLogin',
                'index::signOut',
                'index::index',
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
        session_start();
        $_SESSION['backend'] = $adminSession;
        return $adminSession;

    }

}