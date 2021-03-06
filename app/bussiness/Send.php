<?php

namespace App\Bussiness;

use App\Libs\SendSms;

class Send extends BaseBussiness
{
    public function propServer($admin,$reqData){
        //整理要发送的内容
        $send = [];
        $send['nickname'] = $reqData['nickname'];
        $send['mailtitle'] = $reqData['mailtitle'];
        $send['mailcontent'] = $reqData['mailcontent'];
        $send['itemstr'] = '';

        foreach ($reqData['itemSelected'] as $v){
            $send['itemstr'] .= $v.';';
        }
        $send['itemstr'] = rtrim($send['itemstr'],';');

        //分服务器发送道具
        foreach ($reqData['server_id'] as $server_id){
            //判断服务器是否存在
            $filed = 'server_name,diserver_id,url,server_name';
            $server = $this->getModel('Server')->getById($server_id,$filed);
            if(!$server){
                continue;
            }

            //发送道具请求
            $send['zones'] = $server['diserver_id'];
            $sendItem = $this->getBussiness('GameApi')->sendItem($server['url'],$send);

            //存储记录
            $log = [];
            $log['admin_name'] = $admin['account'];
            $log['admin_no'] = $admin['admin_no'];
            $log['nickname'] = $reqData['nickname'];
            $log['item'] = $send['itemstr'];
            $log['server_name'] = $server['server_name'];
            $log['diserver_id'] = $server['diserver_id'];
            $log['server_url'] = $server['url'];
            if(!$sendItem){
                $log['is_success'] = 0;
            }else{
                $log['is_success'] = 1;
            }
            $this->getModel('SenditemLog')->addLog($log);

        }
    }

    public function propSend($admin,$reqData){
        //整理要发送的内容
        $send = [];
        $send['zones'] = $reqData['zones'];
        $send['nickname'] = $reqData['nickname'];
        $send['mailtitle'] = $reqData['mailtitle'];
        $send['mailcontent'] = $reqData['mailcontent'];
        $send['itemstr'] = '';

        foreach ($reqData['itemSelected'] as $v){
            $send['itemstr'] .= $v.';';
        }
        $send['itemstr'] = rtrim($send['itemstr'],';');

        //发送道具
        $sendItem = $this->getBussiness('GameApi')->sendItem($admin['server_url'],$send);

        //添加发送道具日志
        $log = [];
        $log['admin_name'] = $admin['account'];
        $log['admin_no'] = $admin['admin_no'];
        $log['nickname'] = $reqData['nickname'];
        $log['item'] = $send['itemstr'];
        $log['server_name'] = $admin['server_name'];
        $log['diserver_id'] = $reqData['zones'];
        $log['server_url'] = $admin['server_url'];
        if(!$sendItem){
            $log['is_success'] = 0;
            $this->getModel('SenditemLog')->addLog($log);
            $this->result['status'] = -1;
            $this->result['msg'] = '发送失败';
            return $this->result;
        }

        $log['is_success'] = 1;
        $this->getModel('SenditemSplitLog')->addLog($log);
        $this->result['status'] = -1;
        $this->result['msg'] = '发送成功';
        return $this->result;

    }

    public function tableSend($admin,$reqData){
        //判断该服务器是否存在
        $filed = 'url,server_name,diserver_id';
        $server = $this->getModel('Server')->getById($reqData['server_id'],$filed);
        if(!$server){
            $this->result['status'] = -1;
            $this->result['msg'] = '该服务器不存在';
            return $this->result;
        }

        //获取该标识下的记录
        $filed = 'id,nickname,item,is_send';
        $tables = $this->getModel('SenditemTableLog')->getListByType($reqData['type'],$filed);
        if(!$tables){
            $this->result['status'] = -1;
            $this->result['msg'] = '该标下没有记录';
            return $this->result;
        }

        $send = [];
        $send['zones'] = $server['diserver_id'];
        $send['mailtitle'] = $reqData['mailtitle'];
        $send['mailcontent'] = $reqData['mailcontent'];
        //遍历表，发奖励
        foreach ($tables as $v){
            $send['nickname'] = $v['nickname'];
            $send['itemstr'] = $v['item'];

            //判断昵称是否为空
            if($v['nickname'] == ''){
                continue;
            }

            //判断该条记录是否已经发送成功
            if($v['is_send'] == 1){
                continue;
            }

            //发送奖励
            $sendItem = $this->getBussiness('GameApi')->sendItem($server['url'],$send);

            //记录日志
            $log = [];
            $log['admin_name'] = $admin['account'];
            $log['admin_no'] = $admin['admin_no'];
            $log['nickname'] = $v['nickname'];
            $log['item'] = $send['itemstr'];
            $log['server_name'] = $server['server_name'];
            $log['diserver_id'] = $server['diserver_id'];
            $log['server_url'] = $server['url'];
            if($sendItem){
                $log['is_send'] = 1;
            }

            $this->getModel('SenditemTableLog')->updateById($v['id'],$log);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '发放完成';
        return $this->result;
    }

    public function propRequest($admin,$reqData){
        $item = '';
        foreach ($reqData['itemSelected'] as $v){
            $item .= $v.';';
        }
        $item = rtrim($item,';');

        //添加发送道具日志
        $log = [];
        $log['req_admin_name'] = $admin['account'];
        $log['req_admin_no'] = $admin['admin_no'];
        $log['nickname'] = $reqData['nickname'];
        $log['item'] = $item;
        $log['server_name'] = $admin['server_name'];
        $log['diserver_id'] = $reqData['zones'];
        $log['server_url'] = $admin['server_url'];
        $log['is_send'] = 0;    //1:成功 0:请求中 2:拒绝请求 -1:发送失败
        $log['remark'] = $reqData['remark'];
        $log['mailcontent'] = $reqData['mailcontent'];
        $log['mailtitle'] = $reqData['mailtitle'];

        $add = $this->getModel('SenditemReqLog')->addLog($log);
        if(!$add){
            $this->result['status'] = -1;
            $this->result['msg'] = '发送失败';
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '发送成功';
        return $this->result;
    }

    public function propDeal($admin,$reqData){
        //查询该数据
        $filed = 'is_send,nickname,item,mailtitle,mailcontent,diserver_id,server_url';
        $log = $this->getModel('SenditemReqLog')->getById($reqData['id'],$filed);
        if(!$log){
            $this->result['status'] = -1;
            $this->result['msg'] = '该数据不存在';
            return $this->result;
        }

        switch ($reqData['is_send']){   //1:成功 0:请求中 2:拒绝请求 -1:发送失败
            case '2':
                //判断是否已经操作
                if($log['is_send'] == 2){
                    $this->result['status'] = -1;
                    $this->result['msg'] = '已拒绝该请求';
                    return $this->result;
                }

                //修改数据
                $data['is_send'] = $reqData['is_send'];
                $data['deal_admin_name'] = $admin['account'];
                $data['deal_admin_no'] = $admin['admin_no'];
                $update = $this->getModel('SenditemReqLog')->updateById($reqData['id'],$data);
                if (!$update){
                    $this->result['status'] = -1;
                    $this->result['msg'] = '拒绝失败';
                    return $this->result;
                }

                $this->result['status'] = 1;
                $this->result['msg'] = '拒绝成功';
                return $this->result;
                break;

            case '1':
                //判断是否已经操作
                if($log['is_send'] == 1){
                    $this->result['status'] = -1;
                    $this->result['msg'] = '已通过该请求';
                    return $this->result;
                }

                //发送道具
                $send = [];
                $send['nickname'] = $log['nickname'];
                $send['mailtitle'] = $log['mailtitle'];
                $send['mailcontent'] = $log['mailcontent'];
                $send['itemstr'] = $log['item'];
                $send['zones'] = $log['diserver_id'];
                $sendItem = $this->getBussiness('GameApi')->sendItem($log['server_url'],$send);

                if(!$sendItem){
                    $data['is_send'] = -1;

                    $this->result['status'] = -1;
                    $this->result['msg'] = '发送失败';
                }else{
                    $data['is_send'] = 1;

                    $this->result['status'] = 1;
                    $this->result['msg'] = '发送成功';
                }

                //修改记录
                $data['deal_admin_name'] = $admin['account'];
                $data['deal_admin_no'] = $admin['admin_no'];
                $update = $this->getModel('SenditemReqLog')->updateById($reqData['id'],$data);
                if(!$update){
                    $this->getModel('SenditemReqLog')->updateById($reqData['id'],$data);
                }

                return $this->result;
                break;

            default:
                $this->result['status'] = -1;
                $this->result['msg'] = '暂无此操作';
                return $this->result;
                break;
        }
    }

    public function propCrontab($admin,$reqData){
        //根据server_id查询服务器
        $filed = 'server_name,url,diserver_id';
        foreach ($reqData['server_id'] as $k => $server_id){
            $server = $this->getModel('Server')->getById($server_id,$filed);
            if($server){
                $server_name = $server['server_name'];
                $url = $server['url'];
                $diserver_id = $server['diserver_id'];

                //添加记录
                $crontab = [];
                $crontab['admin_name'] = $admin['account'];
                $crontab['admin_no'] = $admin['admin_no'];
                $crontab['mailtitle'] = $reqData['mailtitle'];
                $crontab['mailcontent'] = $reqData['mailcontent'];
                $crontab['nickname'] = $reqData['nickname'];
                $crontab['item'] = $reqData['item'];
                $crontab['server_name'] = $server_name;
                $crontab['server_url'] = $url;
                $crontab['diserver_id'] = $diserver_id;
                $crontab['is_send'] = 0;
                $crontab['send_time'] = $reqData['send_time'];

                $this->getModel('SenditemCrontab')->addLog($crontab);
            }
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '操作成功';
        return $this->result;
    }

    public function propPlan($admin,$reqData){
        //根据server_id查询服务器
        $filed = 'server_name,url,diserver_id';
        foreach ($reqData['server_id'] as $k => $server_id){
            $server = $this->getModel('Server')->getById($server_id,$filed);
            if($server){
                $server_name = $server['server_name'];
                $url = $server['url'];
                $diserver_id = $server['diserver_id'];

                //添加记录
                $plan = [];
                $plan['admin_name'] = $admin['account'];
                $plan['admin_no'] = $admin['admin_no'];
                $plan['mailtitle'] = $reqData['mailtitle'];
                $plan['mailcontent'] = $reqData['mailcontent'];
                $plan['nickname'] = $reqData['nickname'];
                $plan['item'] = $reqData['item'];
                $plan['server_name'] = $server_name;
                $plan['server_url'] = $url;
                $plan['diserver_id'] = $diserver_id;
                $plan['is_send'] = 0;
                $plan['send_time'] = strtotime($reqData['send_time']);

                $this->getModel('SenditemPlan')->addLog($plan);
            }
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '操作成功';
        return $this->result;
    }
}