<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class TransferController extends ControllerBase
{
    public function transfeStationAction(){
        $postData = $_POST;

        if(!isset($postData['serverId']) || $postData['serverId'] == ''){
            exit;
        }

        //根据serverId发送不同的地址
        switch ($postData['serverId']){
            case '2001':
                $url = 'http://121.40.148.74:6060/recharge?channel=cmgeios';
                break;

            case '2002':
                $url = 'http://121.40.218.67:6060/recharge?channel=cmgeios';
                break;

            case '2003':
                $url = 'http://121.40.176.25:6060/recharge?channel=cmgeios';
                break;

            case '2004':
                $url = 'http://121.40.218.161:6060/recharge?channel=cmgeios';
                break;

            case '2005':
                $url = 'http://121.41.1.134:6060/recharge?channel=cmgeios';
                break;

            default:
                exit;
                break;
        }

        $times = 2;
        for ($i = 0; $i <= $times; $i++){
            $send = $this->getBussiness('GameApi')->transfeStation($url,$postData);
            if($send){
                break;
            }{
                if(!$send && $i == $times){ echo '记日志';
                    //记录日志
                    $str = json_encode($postData);
                    $this->getDI()->get('logger')->log($str, "info", 'transfeStation');
                    exit;
                }
            }
        }

    }



}