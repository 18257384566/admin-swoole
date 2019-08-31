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


        //兑换券管理
        $this->add('/exchange/add','exchange::addView');        //添加兑换券（页面）
        $this->addPost('/exchange/addExchange','exchange::addExchange');  //添加兑换券
        $this->add('/exchange/list','exchange::list');          //兑换券列表
        $this->add('/exchange/card/add','exchange::cardAddView');  //添加道具批次号（页面）

        //区服管理
        $this->add('/zone/list','zone::list');                  //区服列表（页面）

        //管理工具
        $this->add('/manager/prop/send','send::propView');      //发送道具（页面）
        $this->add('/manager/notice/send','send::noticeView');  //推送消息（页面）
        $this->add('/manager/notice/list','manager::noticeList');   //公告列表（页面）
        $this->add('/manager/notice/add','manager::noticeAdd');     //公告添加

        //用户管理
        $this->add('/user/daily/login','user::dailyLogin');     //每日登录（页面）
        $this->add('/user/retain','user::retain');              //每日留存（页面）
        $this->add('/user/login/count','user::loginCount');     //登录统计（页面）
        $this->add('/user/online','user::online');              //实时在线（页面）
        $this->addPost('/user/shipInfo','user::getShipInfo');   //获取用户船队信息
        $this->add('/prop/list','user::propList');              //发送道具（页面）
        $this->add('/prop/excel','user::propListExcel');        //导出道具列表
        $this->add('/user/disableView','user::disableView');    //用户封号（页面）
        $this->addPost('/user/disable','user::disable');        //用户封号
        $this->addPost('/user/notalk','user::notalk');          //用户禁言
        $this->add('/user/info','user::infoView');              //获取用户信息（页面）
        $this->addPost('/user/info','user::info');              //获取用户信息
//        $this->add('/user/shipInfo','user::getShipInfo');       //获取用户船队信息
        $this->add('/user/shipInfo','user::getShipInfoView');   //获取用户船队信息（页面）












        //首页
        $this->add('/{pro_no}/index','index::index');
//        $this->add('/'.$app,'index::index');

        //设置前缀
//        $this->setPrefix();

        //添加表（测试用）
        $this->add('/{pro_no}/addtable','test::addtable');
        $this->add('/{pro_no}/addproject','test::addproject');
        $this->add('/{pro_no}/addadmin','test::addadmin');
        $this->add('/{pro_no}/addchain','test::addchain');
        $this->add('/{pro_no}/notice','test::notice');
        $this->add('/{pro_no}/addDepositOrder','test::addDepositOrder');
        $this->add('/updatePowerAdmin','test::updatePowerAdmin');




//        $this->add('/{pro_no}/login','index::login');
        $this->addPost('/{pro_no}/doLogin','index::doLogin');
//        $this->addPost('/{pro_no}/doLogin','index::doLogin');
        //滑块验证
        $this->add('/{pro_no}/login/captcha','index::captcha');
        $this->add('/{pro_no}/login/sendMessage','index::sendMessage');
        $this->add('/{pro_no}/logout','index::logout');

        //EOS资源管理
        $this->add('/{pro_no}/eosResourceManage','resourcemanage::getEOS');
        $this->add('/{pro_no}/buyRam','resourcemanage::buyEOSRam');
        $this->add('/{pro_no}/buyCpuNet','resourcemanage::buyEOSCpuNet');


        //管理员
        $this->add('/{pro_no}/admin/log','adminlog::getList');
        $this->add('/{pro_no}/admin/logInfo','adminlog::getInfo');
        $this->add('/{pro_no}/admin/list','admin::getList');
        $this->add('/{pro_no}/admin/add','admin::addView');
        $this->addPost('/{pro_no}/admin/add','admin::add');
        $this->add('/{pro_no}/admin/info','admin::infoView');
        $this->addPost('/{pro_no}/admin/info','admin::info');
        $this->add('/{pro_no}/admin/updateStatus','admin::updateStatus');

        //项目详情
        $this->add('/{pro_no}/project/detail','project::detail');
        //各种钱包添加
        $this->addPost('/{pro_no}/projectWallet/add','project::addWallet');
        //确认密码
        $this->addPost('/{pro_no}/projectWallet/passwordConfirm','project::passwordConfirm');
        //编辑冷钱包
        $this->addPost('/{pro_no}/projectWallet/edit','project::editWallet');


        //钱包地址管理
        //钱包类型管理-币种
        $this->add('/{pro_no}/wallettype/list','wallettype::getList');
        $this->add('/{pro_no}/wallettype/updateStatus','wallettype::updateStatus');
        $this->add('/{pro_no}/wallettype/add','wallettype::addView');
        $this->addPost('/{pro_no}/wallettype/add','wallettype::add');
        $this->add('/{pro_no}/wallettype/info','wallettype::infoView');
        $this->addPost('/{pro_no}/wallettype/info','wallettype::info');

        //生成钱包地址
        $this->add('/{pro_no}/walletaddress/add','walletaddress::addView');
        $this->addPost('/{pro_no}/walletaddress/add','walletaddress::add');
        //api
        $this->addPost('/{pro_no}/api/addUserWallet','walletaddress::addUserWallet');
        //钱包管理
        $this->add('/{pro_no}/walletaddressbatch/list','walletaddressbatch::getList');
        //地址详情
        $this->add('/{pro_no}/walletaddress/list','walletaddress::getList');
//        $this->add('/{pro_no}/walletasset/list','walletasset::getList');
        $this->add('/{pro_no}/walletasset/confirm','walletasset::confirmAsset');
        //转出冷钱包
        $this->add('/{pro_no}/walletaddress/transferColdWallet','walletaddress::transferColdWallet');
        //失败重转
        $this->add('/{pro_no}/walletaddress/transferSecondColdWallet','walletaddress::transferSecondColdWallet');
        //api
        $this->addPost('/{pro_no}/api/transferToPurse','walletaddress::transferToPurse');
        $this->addPost('/{pro_no}/api/finishTransferToPurse','walletaddress::finishTransferToPurse');
        //转出历史
        $this->add('/{pro_no}/walletaddress/transferList','walletaddress::transferList');
        $this->add('/{pro_no}/walletaddress/transferInfo','walletaddress::transferInfo');

        //转出手续费流水记录接口
        $this->addPost('/{pro_no}/api/addFeeAddressFlow','walletflow::addFee');
        //通知项目方充值手续费钱包
        $this->addPost('/{pro_no}/api/addFeeNotice','walletaddress::addFeeNotice');


        //等待提现
        $this->add('/{pro_no}/withdraw/waitdeal','withdraw::waitdeal');
        $this->add('/{pro_no}/withdraw/waitdealExcel','withdraw::waitdealExcel');
        //提现中
        $this->add('/{pro_no}/withdraw/withdraw','withdraw::withdrawList');
        $this->add('/{pro_no}/withdraw/withdrawExcel','withdraw::withdrawListExcel');
        //已到账
        $this->add('/{pro_no}/withdraw/successwithdraw','withdraw::successList');
        $this->add('/{pro_no}/withdraw/successwithdrawExcel','withdraw::successListExcel');
        //提现失败
        $this->add('/{pro_no}/withdraw/failwithdraw','withdraw::failList');
        $this->add('/{pro_no}/withdraw/failwithdrawExcel','withdraw::failListExcel');
        //拒绝
        $this->add('/{pro_no}/withdraw/refuseList','withdraw::refuseList');
        $this->add('/{pro_no}/withdraw/refuseExcel','withdraw::refuseExcel');

        //操作
        $this->addPost('/{pro_no}/withdraw/success','withdraw::success');
        $this->addPost('/{pro_no}/withdraw/refuse','withdraw::refuse');
        $this->addPost('/{pro_no}/withdraw/dealMoreOrders','withdraw::dealMoreOrders');
        //失败重转
//        $this->addPost('/{pro_no}/withdraw/failWithdraw','withdraw::failWithdraw');

        //更改提现订单和流水
        $this->addPost('/{pro_no}/api/dealOrder','withdraw::dealOrder');



        //钱包流水
        $this->add('/{pro_no}/walletflow/list','walletflow::getList');
        $this->add('/{pro_no}/walletflow/toExcel','walletflow::getExcel');
        //项目钱包流水
        $this->add('/{pro_no}/walletserverflow/list','walletserverflow::getList');
        $this->add('/{pro_no}/walletserverflow/toExcel','walletserverflow::getExcel');


        //转出未添加币种
        $this->add('/{pro_no}/unknowncoin/transferColdWalletView','unknowncoin::transferView');
        $this->add('/{pro_no}/unknowncoin/selectOrder','unknowncoin::selectOrder');
        $this->addPost('/{pro_no}/unknowncoin/transferColdWallet','unknowncoin::transfer');




        //Echo  Api路由设置
//        $this->add('/api/index','api::index')->via(array('GET', 'OPTIONS'));
        $this->add('/api/createUserApi','api::createUserApi')->via(array('POST', 'OPTIONS'));
        $this->add('/api/withdrawAes','api::withdrawAes')->via(array('POST', 'OPTIONS'));
        $this->add('/api/withdraw','api::withdraw')->via(array('POST', 'OPTIONS'));
        $this->add('/api/getSign','api::getSign')->via(array('POST', 'OPTIONS'));
        $this->add('/api/encryptTest','api::encryptTest')->via(array('POST', 'OPTIONS'));
        $this->add('/api/decryptTest','api::decryptTest')->via(array('POST', 'OPTIONS'));
        $this->add('/api/decryptAes','api::decryptAes')->via(array('POST', 'OPTIONS'));

    }
}