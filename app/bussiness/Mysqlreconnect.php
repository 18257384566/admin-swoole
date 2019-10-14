<?php

namespace App\Bussiness;

use App\Bussiness\BaseBussiness;

class Mysqlreconnect extends BaseBussiness
{

    const MYSQL_TIME_OUT = 60*60*4;

    public function reconnect($processName){
        $key = "inex:string:mysql:reconnect:" . $processName;

        //如果存在
        if($this->redis->get($key) == 1){
            return true;
        }else{
            //设置过期时间
            $this->redis->setex($key,60*60*4,1);
            //重连mysql
            $this->getDI()->getShared('db')->close();
            $this->getDI()->remove('db');
            $mysqlConfig = $this->getDI()->get('config')['database'];
            $this->getDI()->setShared('db',function() use($mysqlConfig){
                return new \Phalcon\Db\Adapter\Pdo\Mysql($mysqlConfig);
            });
        }
    }
}