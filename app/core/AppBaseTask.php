<?php

/**
 * 命令行应用基础类
 * edited by kevin
 * 2016/8/15
 */

namespace App\Core;


use App\Bussiness\BussinessFactory;
use App\Models\ModelFactory;
use Phalcon\Cli\Task;


class AppBaseTask extends Task{
    protected function getBusiness($businessName){
        return BussinessFactory::getBussiness($businessName);
    }

    protected function getModel($modelName){
        return ModelFactory::getModel($modelName);
    }

}