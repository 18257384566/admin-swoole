<?php

namespace App\Bussiness;

class BussinessFactory
{
    private static $_bussiness = array();

    public static function getBussiness($bussinessName)
    {
        $bussinessName = __NAMESPACE__."\\".ucfirst($bussinessName);
        if(!class_exists($bussinessName)){
            throw new \Exception("{$bussinessName}类不存在");
        }
        if(!isset(self::$_bussiness[$bussinessName]) ||empty(self::$_bussiness)) {
            self::$_bussiness[$bussinessName] = new $bussinessName();
        }
        return self::$_bussiness[$bussinessName];
    }
}