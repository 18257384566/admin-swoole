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
        $server_url = $this->dispatcher->getParam('admin')['server_url'];
        $server_url = trim(strrchr($server_url, '/'),'/');  //去掉http
        $server_url = strstr($server_url,':',-1);   //去掉端口号

        $this->config->redis->host = $server_url;

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

            case 'order':

                $key = 'User_Orders:'.$user_id;
                $orderList = $this->redis->sMembers($key);
                if(!$orderList){
                    return [];
                }

                //遍历订单信息
                $userInfo = [];
                foreach ($orderList as $k => $order_no){
                    $key = "User_Order:$order_no";
                    $order_info = $this->redis->hgetall($key);
                    if($order_info){
                        $userInfo[] = $order_info;
                    }
                }

                //获取道具表
                $itemList = $this->getBussiness('GameApi')->getItemList();
                foreach ($userInfo as &$v){ //var_dump($v);
                    $ItemStr = json_decode($v['ItemStr'],true);
                    $v['ItemName'] = $ItemStr[0]['ItemId'];
                    $v['ItemQuantity'] = $ItemStr[0]['ItemQuantity'];
                }

                break;
        }

        //echo '<pre>';var_dump($userInfo);exit;
        return $userInfo;
    }
}