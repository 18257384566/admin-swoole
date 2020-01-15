<?php

use Phalcon\Di\FactoryDefault\Cli as CliDi;
use Phalcon\Cli\Console as ConsoleApp;

/**
 * Get config service for use in inline setup below
 */
$config = include '../app/config/config.php';

/**
 * Include Services
 */
include  '../app/config/services.php';



/**
 * Include Autoloader
 */
include '../app/config/loader.php';

/**
 * Create a console application
 */
//$console = new ConsoleApp($di);



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

