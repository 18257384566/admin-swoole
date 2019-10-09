<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class GameApi extends BaseBussiness
{
    public $server_url = '';

    public function __construct()
    {
        $admin = $this->dispatcher->getParam('admin');
        $this->server_url = $admin['server_url'];
    }

    public function getusershipinfo($reqData){
        $data = [];
        $data['nickname'] = $reqData['nickname'];
        $url = $this->server_url.'/manager/getusershipinfo';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return $result['message'];
    }

    public function getItemList(){
        $url = $this->server_url.'/gm/getitemlist';
        try{
            $result = $this->functions->http_request_code($url, 'GET');
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        unset($result['success']);
        return $result;
    }

    public function getZoneList(){
        $url = $this->server_url.'/gm/getzonelist';
        try{
            $result = $this->functions->http_request_code($url, 'GET');
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        unset($result['success']);
        return $result;
    }

    public function ban($data){
        $url = $this->server_url.'/manager/ban';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return $result;
    }

    public function getUserInfo($reqData){
        $url = $this->server_url.'/manager/getuserinfo';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$reqData);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }
        echo '<pre>';

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return $result['message'];
    }

    public function talkban($data){
        $url = $this->server_url.'/manager/talkban';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return $result;
    }

    public function analyze($data){
        $url = $this->server_url.'/manager/analyze';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return $result['message'];
    }

    public function sendItem($server_url,$data){
        $url = $server_url.'/manager/senditem';
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
//            var_dump($result);exit;
            return false;
        }
        return true;
    }

    public function transfeStation($url,$data){
        try{
            $result = $this->functions->http_request_code($url, 'POST',$data);
            if(!$result){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if(!isset($result['success']) || $result['success'] != 'true'){
            return false;
        }
        return true;
    }



}