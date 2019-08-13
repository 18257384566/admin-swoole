<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

use Phalcon\Di\FactoryDefault;
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
$__APP_IN_DEBUG = true;


//跨域
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers:token,lang,tokenpassword,Origin, Content-Type, Cookie, Accept, application/json');
//header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE');
header('Access-Control-Allow-Methods:GET,POST,OPTIONS');
header('Access-Control-Allow-Credentials:true');
header('Access-Control-Expose-Headers:token,lang,tokenpassword');

//ini_set('session.gc_maxlifetime', 86400);
try {

    /**
     * The FactoryDefault Dependency Injector automatically registers
     * the services that provide a full stack framework.
     */
    $di = new FactoryDefault();

    //
    include APP_PATH . '/config/waf.php';

    /**
     * Handle routes
     */
    include APP_PATH . '/config/router.php';

    /**
     * Read services
     */
    include APP_PATH . '/config/services.php';
    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    /**
     * Include Autoloader
     */
    include APP_PATH . '/config/loader.php';

    //引入以太坊开发工具包
    //include APP_PATH . '/../vendor/autoload.php';

    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Application($di);

    //echo str_replace(["\n","\r","\t"], '', $application->handle()->getContent());

    echo $application->handle()->getContent();

} catch (\Exception $e) {
    if($__APP_IN_DEBUG){
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
