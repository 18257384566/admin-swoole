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
    public function getusershipinfo($reqData){
        $data = [];
        $data['nickname'] = $reqData['nickname'];
        $url = $this->config['leeonUrl'].'/manager/getusershipinfo';
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
        $url = $this->config['gameUrl'].'/gm/getitemlist';
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



}