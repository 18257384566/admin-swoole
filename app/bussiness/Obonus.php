<?php

namespace App\Bussiness;


class Obonus extends BaseBussiness
{
    public function obonusUse($reqData){
        //根据区服id查询url
        $filed = 'url';
        $server = $this->getModel('Server')->getByDiserver($reqData['zones'],$filed);
        if(!$server){
            $this->result['status'] = -1;
            $this->result['msg'] = '该区服id不存在';
            return $this->result;
        }

        //判断该 奖励返还编码 是否存在
        $filed = 'obonus_code,is_used,request_num,item_id';
        $obonus = $this->getModel('Obonus')->getByExchangeCode($reqData['obonus_code'],$filed);
        if(!$obonus){
            $this->result['status'] = -1;
            $this->result['msg'] = '暂无奖励返还';
            return $this->result;
        }

        if($obonus['is_used'] != 0){
            $this->result['status'] = -1;
            $this->result['msg'] = '奖励返还已领取';
            return $this->result;
        }

        //记录
        $data = [];
        $data['user_name'] = $reqData['user_name'];
        $data['is_used'] = 1;
        $data['used_time'] = time();
        $obonusUpdate = $this->getModel('Obonus')->updateByObonusCode($reqData['obonus_code'],$data);
        if(!$obonusUpdate){
            $this->result['status'] = -1;
            $this->result['msg'] = '兑换失败';
            return $this->result;
        }

        //发送奖励返还
        $num = $obonus * 2;
        $requestData = [];
        $requestData['zones'] = $reqData['zones'];
        $requestData['nickname'] = $reqData['user_name'];
        $requestData['itemstr'] = $obonus['item_id'].','.$num;
        $requestData['mailtitle'] = '您的奖励返还成功';
        $requestData['mailcontent'] = '您的奖励返还成功，请查收';
        $senditem = $this->getBussiness('GameApi')->sendItem($server['url'],$requestData);
        if(!$senditem){
            $data['is_used'] = 0;
            $this->getModel('Obonus')->updateByObonusCode($reqData['obonus_code'],$data);

            $this->result['status'] = -1;
            $this->result['msg'] = '兑换失败';
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '兑换成功';
        return $this->result;
    }
}