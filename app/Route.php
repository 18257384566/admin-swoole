<?php

namespace App;

use Phalcon\Mvc\Router\Group as RouterGroup;

class Route extends RouterGroup
{
    //组合路由
    public function initialize($app)
    {

        //设置路由命名指向空间
        $this->setPaths([
            'namespace' => 'App\Controllers'
        ]);

        //api
        $this->addPost('/exchange/exchange','exchange::exchange');  //兑换券（兑换）
        $this->add('/api/notice/get','manager::noticeApi');             //获取公告



        //登陆
        $this->add('/','index::login');
        $this->addPost('/admin/doLogin','index::doLogin');      //登录
        $this->add('/admin/signOut','index::signOut');          //退出登录
        $this->add('/admin/index','index::index');              //首页


        //管理员列表
        $this->add('/admin/list','admin::getList');
        $this->add('/admin/updateStatus','admin::updateStatus');
        $this->add('/admin/add','admin::addView');              //添加用户（页面）
        $this->add('/admin/addAdmin','admin::addAdmin');        //添加用户
        $this->add('/admin/log','admin::adminLog');             //管理员日志
        $this->add('/admin/server/list','admin::serverList');   //服务器列表
        $this->add('/admin/server/add','admin::serverAdd');     //服务器添加
        $this->add('/admin/server/del','admin::serverDel');     //服务器删除
        $this->add('/admin/server/updateView','admin::serverUpdateView'); //服务器修改（页面）
        $this->add('/admin/server/update','admin::serverUpdate');   //服务器修改
        $this->add('/admin/diserver/list','admin::diserverList');   //区服列表
        $this->add('/admin/diserver/add','admin::diserverAdd');     //添加区服
        $this->add('/admin/diserver/del','admin::diserverDel');     //删除区服
        $this->add('/admin/getzonelist','admin::getzonelist');
        $this->add('/admin/channel/list','admin::channelList');     //渠道列表


        //兑换券管理
        $this->add('/exchange/add','exchange::addView');        //添加兑换券（页面）
        $this->addPost('/exchange/addExchange','exchange::addExchange');  //添加兑换券
        $this->add('/exchange/list','exchange::list');          //兑换券列表
        $this->add('/exchange/card/add','exchange::cardAddView');  //添加道具批次号（页面）

        //区服管理
        $this->add('/zone/list','zone::list');                  //区服列表（页面）

        //管理工具
        $this->add('/manager/prop/send','send::propView');                  //发送道具（页面）
        $this->addPost('/manager/prop/send','send::prop');                  //发送道具
        $this->add('/manager/prop/serverSend','send::propServerView');      //发送道具多服（页面）
        $this->addPost('/manager/prop/serverSend','send::propServer');      //发送道具多服
        $this->add('/manager/notice/send','send::noticeView');              //推送消息（页面）
        $this->add('/manager/notice/list','manager::noticeList');           //公告列表（页面）
        $this->add('/manager/notice/add','manager::noticeAdd');             //公告添加

        //用户管理
        $this->add('/user/daily/login','user::dailyLogin');     //每日登录（页面）
        $this->add('/user/retain','user::retain');              //每日留存（页面）
        $this->add('/user/login/count','user::loginCount');     //登录统计（页面）
        $this->add('/user/online','user::online');              //实时在线（页面）
        $this->addPost('/user/online','user::onlineQuery');     //实时在线
        $this->addPost('/user/shipInfo','user::getShipInfo');   //获取用户船队信息
        $this->add('/prop/list','user::propList');              //发送道具（页面）
        $this->add('/prop/excel','user::propListExcel');        //导出道具列表
        $this->add('/user/disableView','user::disableView');    //用户封号（页面）
        $this->add('/user/distalkView','user::distalkView');    //用户禁言（页面）
        $this->addPost('/user/disable','user::disable');        //用户封号
        $this->addPost('/user/notalk','user::notalk');          //用户禁言
        $this->add('/user/info','user::infoView');              //获取用户信息（页面）
        $this->addPost('/user/info','user::info');              //获取用户信息
//        $this->add('/user/shipInfo','user::getShipInfo');       //获取用户船队信息
        $this->add('/user/shipInfo','user::getShipInfoView');   //获取用户船队信息（页面）


        //获取实时数据
        $this->add('/data/update','data::update');              //更新redis数据

        //返还奖励
        $this->add('/obonus/add','obonus::addView');                //添加返还奖励（页面）
        $this->addPost('/obonus/addObonus','obonus::addObonus');    //添加返还奖励
        $this->add('/obonus/list','obonus::list');                  //返还奖励列表
        $this->addPost('/api/obonus/use','obonus::use');            //返还奖励使用
        $this->add('/api/obonus/use','obonus::use');            //返还奖励使用

    }
}