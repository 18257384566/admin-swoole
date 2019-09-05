<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;

class DataController extends ControllerBase
{
    public function updateAction(){
        $server_url = $this->dispatcher->getParam('admin')['server_url'];
        $server_url = trim(strrchr($server_url, '/'),'/');  //去掉http
        $server_url = strstr($server_url,':',-1);   //去掉端口号

        //获取aof路径
        $route = $this->config->aof;

        $cmd = "scp -r abin@$server_url:$route /usr/local/redis;./redis-cli -h 127.0.0.1 -p 6379 --pipe < appendonly.aof;rm appendonly.aof  && echo success";
        //$cmd = 'cd /usr/local/redis;mkdir test && echo success';
        //$cmd = 'whoami';
        $result = shell_exec($cmd);
        if($result){
            $this->functions->alert('更新成功');
            exit;
        }else{
            $this->functions->alert('更新失败');
            exit;
        }
    }
}