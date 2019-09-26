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
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
//use Phalcon\Di\FactoryDefault;

//use App\Libs\MyRedis;
use Phalcon\Di\FactoryDefault\Cli as CliDI;
//if (!isset($di)){
//    $di = new FactoryDefault();
//}

use App\Core\AppBaseLogger;



$di = new CliDI();
/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});



/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
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

    return $connection;
});


/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});




//注册常用函数类库
$di->setShared('functions',function(){
    return new Functions();
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

//注册日志
$di->setShared('logger',function(){
    return AppBaseLogger::getInstance();
});





