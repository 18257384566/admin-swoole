<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs([
    APP_PATH . '/tasks'
]);
/*$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir
    ]
)->register();*/

$loader->registerNamespaces(
    [
        'App'             => APP_PATH,
        'App\Controllers' => APP_PATH . '/controllers',
        'App\Core'        => APP_PATH . '/core',
        'App\Bussiness'   => APP_PATH . '/bussiness',
        'App\Models'      => APP_PATH . '/models',
        'App\Plugins'     => APP_PATH . '/plugins',
        'App\Libs'        => APP_PATH . '/libs',
    ]
);

$loader->register();
