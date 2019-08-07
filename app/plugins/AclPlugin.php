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
        //获取resource名称
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();
        $resource = $controller."::".$action;
        if($this->aclAuth($resource)){
            return true;
        }
//        exit('router error');
    }

    public function aclAuth($resource)
    {
        $rights = array(
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

            ),
//            'backendapi'  => array(
//                'walletflow::addFee',
//                'walletaddress::transferToPurse',
//                'walletaddress::finishTransferToPurse',
//                'withdraw::dealOrder',
//                'walletaddress::addUserWallet',
//                'walletaddress::addFeeNotice',
//                //test
//                'admin::add',
//                'test::notice',
//                'test::addproject',
//                'test::addadmin',
//                'test::addchain',
//                'test::addDepositOrder',
////                'test::editPowerAdmin',
//                'test::updatePowerAdmin',
//            ),
//            'api'  => array(
//                'api::createUserApi',
//                'api::withdrawAes',
//                'api::withdraw',
//                'api::getSign',
//                'api::encryptTest',
//                'api::decryptTest',
//                'api::decryptAes',
//            ),
        );
        if(in_array($resource,$rights['visitor'])){
            return true;
        }

        if(in_array($resource,$rights['auth'])){
            return $this->checkLogin();
        }

        if(in_array($resource,$rights['backendapi'])){
            //设置数据表前缀
            $pro_no = $this->dispatcher->getParam('pro_no');
            $this->config->database['prefix'] = $this->config->database['prefix'].$pro_no.'_';

            return $this->checkBackendApi();
        }

        if(in_array($resource,$rights['api'])){
//            return $this->checkLogin();
            return true;
        }
        return false;
    }

    public function checkLogin()
    {
        if(isset($_SESSION['expiretime'])) {
            if($_SESSION['expiretime'] < time()) {
                $this->session->remove("backend");
                $this->functions->alert('请重新登陆','/admin/login');
                exit;
            } else {
                $_SESSION['expiretime'] = time() + $this->config->lifetime['login'];
            }
        }

        $adminSession = $this->session->get('backend');
        $this->dispatcher->setParam('admin',$adminSession);

        return true;

    }

    public function checkBackendApi(){
        return true;
    }

}