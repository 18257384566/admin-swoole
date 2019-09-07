<?php
/**
 * Created by PhpStorm.
 * User: dlab-xsy
 * Date: 2019/9/7
 * Time: 4:22 PM
 */

namespace App\Bussiness;


class redis extends BaseBussiness
{
    //public $redis = '';

    public function __construct()
    {
        //获取连接ip
        $server_id= $this->dispatcher->getParam('admin')['server_id'];
        $filed = 'redis_url';
        $server = $this->getModel('Server')->getById($server_id,$filed);

        $this->config->redis->host = $server['redis_url'];

    }

    public function getUserInfo($nickname,$type){
        $key = 'Game_Nickname';
        $user_id = $this->redis->hGet($key,$nickname);
        if(!$user_id){
            $this->functions->alert('该用户不存在');
            exit;
        }

        switch ($type){
            case 'ship':    //船

                $key = 'User_Warships:'.$user_id;
                $ships = $this->redis->sMembers($key);
                if(!$ships){
                    return [];
                }

                //遍历用户船只信息
                $userInfo = [];
                foreach ($ships as $ship){
                    $key = "User_Warship:$user_id:$ship";
                    $ship_info = $this->redis->hgetall($key);
                    if($ship_info){
                        $userInfo[] = $ship_info;
                    }
                }

                break;
            case 'res':     //资源

                $key = 'User_Res:'.$user_id;
                $userInfo = $this->redis->hgetall($key);

                $arr = ['Parts','SkillPoint','WeaponPoint','AcsEvolutionPoint','Gold','Fuel','Ticket','ArmyTick','Material','Diamond','Friendship','ActivePoints'];
                foreach ($arr as $k => $v)
                    if(!isset($userInfo[$v])){
                        $userInfo[$v] = 0;
                    }

                break;
            case 'construction':    //建筑

                $key = 'User_Constructions:'.$user_id;
                $constructions = $this->redis->sMembers($key);
                if(!$constructions){
                    return [];
                }

                //遍历用户船只信息
                $userInfo = [];
                foreach ($constructions as $construction){
                    $key = "User_Construction:$user_id:$construction";
                    $construction_info = $this->redis->hgetall($key);
                    if($construction_info){
                        $userInfo[] = $construction_info;
                    }
                }

                break;
        }

        //echo '<pre>';var_dump($userInfo);exit;
        return $userInfo;
    }
}