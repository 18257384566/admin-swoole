<?php

use Phalcon\Di\FactoryDefault\Cli as CliDi;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Di\FactoryDefault;

/**
 * Get config service for use in inline setup below
 */
//var_dump(APP_PATH . '/../config/config.php');exit;
$config = include APP_PATH . '/../config/config.php';

/**
 * Include Services
 */
include APP_PATH . '/../config/services.php';



/**
 * Include Autoloader
 */
include APP_PATH . '/../config/loader.php';

/**
 * Create a console application
 */
$di = new FactoryDefault();
$console = new ConsoleApp($di);



/**
 * Process the console arguments
 */
//$arguments = [];

//foreach ($argv as $k => $arg) {
//    if ($k == 1) {
//        $arguments['task'] = $arg;
//    } elseif ($k == 2) {
//        $arguments['action'] = $arg;
//    } elseif ($k >= 3) {
//        $arguments['params'][] = $arg;
//    }
//}

//try {
//
//    /**
//     * Handle
//     */
//    $console->handle($arguments);
//
//    /**
//     * If configs is set to true, then we print a new line at the end of each execution
//     *
//     * If we dont print a new line,
//     * then the next command prompt will be placed directly on the left of the output
//     * and it is less readable.
//     *
//     * You can disable this behaviour if the output of your application needs to don't have a new line at end
//     */
//    if (isset($config["printNewLine"]) && $config["printNewLine"]) {
//        echo PHP_EOL;
//    }
//
//} catch (Exception $e) {
//    echo $e->getMessage() . PHP_EOL;
//    echo $e->getTraceAsString() . PHP_EOL;
//    exit(255);
//}

