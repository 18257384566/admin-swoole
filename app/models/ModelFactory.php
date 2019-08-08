<?php

namespace App\Models;

class ModelFactory
{
    private static $_models = array();

    public static function getModel($modelName)
    {
        $modelName = __NAMESPACE__."\\".ucfirst($modelName);
        if(!class_exists($modelName)){
            throw new \Exception("{$modelName}类不存在");
        }
        if(!isset($_models[$modelName]) || empty($_models[$modelName])){
            self::$_models[$modelName] = new $modelName;
        }
        return self::$_models[$modelName];
    }
}