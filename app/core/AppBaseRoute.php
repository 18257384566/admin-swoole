<?php

namespace App\Core;

use Phalcon\Mvc\Router;
use App\Route;

class AppBaseRoute extends Router
{
    public function __construct($boolean,$app)
    {
        parent::__construct($boolean);
        $this->mount(new Route($app));
        $this->removeExtraSlashes(true);
        $this->notFound([
            'namespace' => 'App\Controllers',
            'controller' => 'index',
            'action' => 'notFound'
        ]);
    }
}