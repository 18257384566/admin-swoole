<?php

namespace App\Bussiness;

use App\Libs\SendSms;

class User extends BaseBussiness
{
    public function getUserInfo($user_id,$reqData){
        switch ($reqData['type']){
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