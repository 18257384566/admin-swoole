<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Flash\Direct as Flash;

use App\Core\AppBaseRoute;
use App\Core\PaValidation;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use App\Plugins\AclPlugin;
use Phalcon\Translate\Adapter\NativeArray;
use App\Libs\Functions;
use App\Libs\Curl;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Profiler as ProfilerDb;
use Common\Common;
use App\Core\AppBaseLogger;

//use App\Libs\MyRedis;
//use Phalcon\Di\FactoryDefault\Cli as CliDI;
if (!isset($di)){
    $di = new FactoryDefault();
}



//$di = new CliDI();
/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);
    return $url;
});



/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);
    $view->registerEngines([
        '.volt' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions([
                'compiledPath' => $config->application->cacheDir,
                'compiledSeparator' => '_'
            ]);

            return $volt;
        },
        '.phtml' => PhpEngine::class

    ]);

    return $view;
});


//向$di里注册profiler服务

$di->set('profiler', function () {
    return new ProfilerDb();
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {

    //新建一个事件管理器
    $eventsManager = new EventsManager();

    //从di中获取共享的profiler实例
    $profiler = $this->getProfiler();

    //监听所有的db事件
    $eventsManager->attach('db', function($event, $connection) use ($profiler) {
        //一条语句查询之前事件，profiler开始记录sql语句
        if ($event->getType() == 'beforeQuery') {
            $profiler->startProfile($connection->getSQLStatement());
        }
        //一条语句查询结束，结束本次记录，记录结果会保存在profiler对象中
        if ($event->getType() == 'afterQuery') {
            $profiler->stopProfile();
        }
    });

    $config = $this->getConfig();
    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    $connection = new $class($params);

    //将事件管理器绑定到db实例中
    $connection->setEventsManager($eventsManager);


    return $connection;
});


/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});


/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    return new Flash([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    session_set_cookie_params(86400);
    $session = new SessionAdapter();
    $session->start();
    return $session;


//    $config = $this->getConfig();
//    $sessionConfig = $config['session'];
//    $adapter = $sessionConfig->adapter;
//    if (class_exists($adapter)) {
//        $options = Common::convertArrKeyUnderline($sessionConfig->options->toArray());
////        $options = $sessionConfig->options->toArray();
//        $session = new $adapter($options);
//    } else {
//        throw new Exception('session出错：' . $adapter . '类不存在');
//    }
//    if ($sessionConfig->auto_start) {
//        $session->start();
//    }
//    return $session;

});

//注册路由
$di->set('router',function () {
    $router = new AppBaseRoute(false,'app');
    return $router;
});

//注册数据验证服务
$di->set('paValidation',function(){
    return new PaValidation();
});

//注册调度器
$di->setShared('dispatcher',function(){
    //创建一个事件管理
    $eventsManager = new EventsManager();
    $eventsManager->attach("dispatch:beforeDispatchLoop", function($event,$dispatcher) {});
    $eventsManager->attach("dispatch:beforeDispatchLoop", function($event,$dispatcher) {});
    $eventsManager->attach('dispatch:beforeExecuteRoute', new AclPlugin);
    $dispatcher = new MvcDispatcher();
    $dispatcher->setDefaultNamespace('App\Controllers');
    $dispatcher->setEventsManager($eventsManager);
    return $dispatcher;
});


//注册常用函数类库
$di->setShared('functions',function(){
    return new Functions();
});

$di->setShared('curl',function(){
    return new Curl();
});

//注册事物
/*$di->setShared('transactions',function(){
    return new TransactionManager();
});*/

//注册事务
$di->setShared('transactions',function(){
    $transaction =  new TransactionManager();
    $transaction->setDbService('db');
    return $transaction;
});

//注册redis
$di->setShared('redis',function(){
    $config = $this->getConfig();
    $configRedis = $config['redis'];
    $redis =  new \Redis();
    $redis->connect( $configRedis['host'] , $configRedis['port']);
    if (!empty( $configRedis['password'] )){
        $redis->auth( $configRedis['password'] );
    }
    return $redis;
});


//注册导出excel
$di -> setShared('PHPExcel', function() {
    require_once APP_PATH.'/plugins/PHPExcel.php';
    return new PHPExcel();
});

//注册日志
$di->setShared('logger',function(){
    return AppBaseLogger::getInstance();
});



