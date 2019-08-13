<?php

namespace App\Bussiness;


class Exchange extends BaseBussiness
{
    public function exchange($reqData){
        //判断该编号是否存在
        $filed = 'card_no,is_used';
        $exchange_code = $this->getModel('Exchange')->getByExchangeCode($reqData['exchange_code'],$filed);
        if(!$exchange_code){
            $this->result['status'] = -1;
            $this->result['msg'] = '该兑换券不存在';
            return $this->result;
        }

        if($exchange_code['is_used'] != 0){
            $this->result['status'] = -1;
            $this->result['msg'] = '亲，该兑换劵已被使用';
            return $this->result;
        }

        //判断道具编码是否存在
        if(!isset($this->config['exchangeCard'][$exchange_code['card_no']])){
            $this->result['status'] = -1;
            $this->result['msg'] = '道具编码不存在';
            return $this->result;
        }

        $card = $this->config['exchangeCard'][$exchange_code['card_no']];
        if($card->expire != 0 && $card->expire < time()){
            $this->result['status'] = -1;
            $this->result['msg'] = '亲，该道具兑换已过期';
            return $this->result;
        }

        //兑换成功修改数据
        $exchange['user_name'] = $reqData['user_name'];
        $exchange['is_used'] = 1;
        $exchange['used_time'] = time();
        $update = $this->getModel('Exchange')->updateByExchangeCode($reqData['exchange_code'],$exchange);
        if(!$update){
            $this->result['status'] = -1;
            $this->result['msg'] = '兑换失败';
            return $this->result;
        }

        //发送礼包请求
        $times = 1;
        for ($i = 0; $i <= $times; $i++){
            $send = $this->senditem($reqData,$card);
            if($send){
                break;
            }{
                if(!$send && $i == $times){ echo '记日志';
                    //记录日志
                    $str = "zones:".$reqData['zones'].";nickname:".$reqData['user_name'].";itemstr.".$card->item.','.$card->num;
                    $this->getDI()->get('logger')->log($str, "info", 'senditem');
                }
            }
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '兑换成功';
        return $this->result;
    }

    //发送礼包请求
    public function senditem($reqData,$card){
        //发送礼包请求
        $requestData = [];
        $requestData['zones'] = $reqData['zones'];
        $requestData['nickname'] = $reqData['user_name'];
        $requestData['itemstr'] = $card->item.','.$card->num;
        $requestData['mailtitle'] = '1';
        $requestData['mailcontent'] = '1';
        $url = $this->config['gameUrl'].'/manager/senditem';
        try{
            $senditem = $this->functions->http_request_code($url, 'POST',$requestData);
            if(!$senditem){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        if($senditem['success'] != 'true'){
            return false;
        }

        return true;
    }

}