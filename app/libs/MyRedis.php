<?php

namespace App\Libs;

use \Redis;

class MyRedis extends Redis
{
    protected static $_instance = null;
    protected $dblink = null;
    protected $_error = '';

    public static function getInstance($redis_config = array())
    {
        if(!self::$_instance){
            $self_instance = new self($redis_config);
            $self_instance->auth($redis_config['password']);
            self::$_instance = $self_instance;
        }
        return self::$_instance;
    }

    public function __construct(array $config = null)
    {
        if(!extension_loaded('Redis')){
            throw new \Exception('不支持redis扩展');
        }
        if(is_array($config)){
            try {
                $this->dblink = parent::connect($config['host'],$config['port'],$config['lifetime'],$config['password']);
            } catch(\Exception $e) {
                throw new \Exception('redis连接失败');
            }
        }
    }
}