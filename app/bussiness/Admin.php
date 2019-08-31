<?php

namespace App\Bussiness;

use App\Libs\SendSms;

class Admin extends BaseBussiness
{
    public function doLogin($reqData){
        //判断有没有账户信息 key=pro_no:admin_account（账户，手机号，密码，状态，错误次数）
        $filed = 'password,admin_no,admin_name,real_name,status,is_super,permissions,role';
        $admin = $this->getModel('Admin')->getByAdmin($reqData['name'],$filed);
        if(!$admin){
            $this->result['status'] = -1;
            $this->result['msg'] = '管理员不存在';
            return $this->result;
        }

        if($admin['password'] != md5($reqData['password'])){
            $this->result['status'] = -1;
            $this->result['msg'] = '密码错误';
            return $this->result;
        }

        //记录日志
        $sqlData['admin_no'] = $admin['admin_no'];
        $sqlData['admin_name'] = $reqData['name'];
        $sqlData['server_name'] = $reqData['server_name'];
        $this->getModel('AdminLog')->addLog($sqlData);


        $this->result['status'] = 1;
        $this->result['msg'] = '登陆成功';
        $this->result['data'] = $admin;
        return $this->result;

    }

    public function updateStatus($reqData){
        //判断管理员是否存在
        $filed = 'is_super';
        $admin = $this->getModel('Admin')->getById($reqData['id'],$filed);
        if(!$admin){
            $this->result['status'] = -1;
            $this->result['msg'] = '管理员不存在';
            return $this->result;
        }

        //判断用户是否是超级管理
        if($reqData['status'] == 0 && $admin['is_super'] == 1){
            $this->result['status'] = -1;
            $this->result['msg'] = '不可禁用超级管理员';
            return $this->result;
        }

        //修改用户状态
        $data['status'] = $reqData['status'];
        $update = $this->getModel('Admin')->updateById($reqData['id'],$data);
        if(!$update){
            $this->result['status'] = 1;
            $this->result['msg'] = '修改失败';
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '修改成功';
        return $this->result;
    }

    public function add($reqData){
        //判断管理员账户是否重复
        $filed = 'id';
        $adminFromSql = $this->getModel('Admin')->getByAdmin($reqData['admin_name'],$filed);
        if($adminFromSql){
            $this->result['status'] = -1;
            $this->result['msg'] = '该账户名已被使用';
            return $this->result;
        }

        //判`断手机号是否重复
        $filed = 'id';
        $adminFromSql = $this->getModel('Admin')->getPhone($reqData['phone'],$filed);
        if($adminFromSql){
            $this->result['status'] = -1;
            $this->result['msg'] = '该手机号已被使用';
            return $this->result;
        }

        //数据处理
        $reqData['admin_no'] = $this->functions->createNo();
        $reqData['password'] = md5($reqData['password']);
        $id = $this->getModel('Admin')->add($reqData);
        if(!$id){
            $this->result['status'] = 1;
            $this->result['msg'] = '添加失败';
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        return $this->result;
    }










    public function sendMessage($phone){
        //创建对应的数据库记录(验证码)
        $reqData['phone'] = $phone;

        //判断验证码
        $code = $this->getModel('AdminPhone')->getLastByPhone($reqData['phone'],$filed='code,status');
        if($code['status'] == 1){
            $reqData['code'] = $code['code'];
        }else{
            $reqData['code'] = $this->functions->GetfourStr(6);
        }
        $reqData['created_at'] = $reqData['updated_at'] =time();
        $tmpPhone = $this->getModel('AdminPhone')->add($reqData);
        if (!$tmpPhone) {
            $this->result['status'] = -1;
            $this->result['msg'] = "短信发送失败";
            return $this->result;
        }

        //记录过期时间（redis）
        $this->getBussiness('RedisCache')->codeDeadline($reqData['phone'],$reqData['code']);


        $sms['templateId'] = '21238';
        $sms['phone'] = $reqData['phone'];
        $sms['vars'] = '{"%code%":"'.$reqData['code'].'"}';
        $SendSms = new SendSms();
        $SendSms->send_sms($sms);

        $this->result['status'] = 1;
        $this->result['msg'] = "短信发送成功";
        return $this->result;
    }


    public function getList($page,$limit){
        return $this->getModel('Admin')->getList($filed='admin_name,phone,status,id',$page,$limit);
    }

    public function getById($id){
        return $this->getModel('Admin')->getById($id,$filed='*');
    }

    public function getByWhere($pro_no,$whereFiled,$whereData){
        return $this->getModel('Admin')->getByWhere($pro_no,$whereFiled,$whereData,$filed='*');
    }

    public function updateById($id,$data){
        return $this->getModel('Admin')->updateById($id,$data);
    }

    public function addFornewproject($pro_no,$reqData){
        if($reqData['is_super'] != 1 && $reqData['permissions'] == []){
            $this->result['status'] = -1;
            $this->result['msg'] = '请选择权限';
            return $this->result;
        }
        //判断管理员账户是否重复
        $adminFromSql = $this->getModel('Admin')->getByWhere($pro_no,'admin_name',$reqData['admin_name'],$filed='*');
        if($adminFromSql){
            $this->result['status'] = -1;
            $this->result['msg'] = '该账户名已重复';
            return $this->result;
        }
        //判断手机号是否重复
        $adminFromSql = $this->getModel('Admin')->getByWhere($pro_no,'phone',$reqData['phone'],$filed='*');
        if($adminFromSql){
            $this->result['status'] = -1;
            $this->result['msg'] = '该手机号已重复';
            return $this->result;
        }

        //数据处理
        $reqData['pro_no'] = $pro_no;
        $project = $this->getBussiness('Project')->getDetail($pro_no);
        $reqData['pro_name'] = $project['pro_name'];
        $reqData['admin_no'] = $this->functions->createNo();
        $reqData['password'] = md5($reqData['password']);
        $reqData['created_at'] = $reqData['updated_at'] = time();

        if($reqData['permissions'] != null){
            $reqData['permissions'] = '0,'.$reqData['permissions'].',0';
        }else{
            $reqData['permissions'] = '0';
        }

        $id = $this->getModel('Admin')->add($reqData);
        //redis
        $redisData = [
            'id'=>$id,
            'account'=>$reqData['admin_name'],
            'permissions'=>$reqData['permissions'],
            'is_super'=>$reqData['is_super'],
            'is_power'=>$reqData['is_power'],
            'status'=>1,
        ];
        $key = $reqData['pro_no'].":".$reqData['admin_no'];
        $this->getBussiness('RedisCache')->createAdmin($key,$redisData);

        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        return $this->result;

    }

    public function info($pro_no,$id,$reqData){
        //数据处理
        $adminSession = $this->session->get('backend');
        $reqData['pro_no'] = $adminSession['pro_no'];
        $adminRedis = $this->getBussiness('RedisCache')->getAdmin($adminSession['pro_no'].':'.$adminSession['admin_no']);
        $project = $this->getBussiness('Project')->getDetail($reqData['pro_no']);

        if($reqData['is_power'] != 1 && $reqData['permissions'] == []){
            $this->result['status'] = -1;
            $this->result['msg'] = '请选择权限';
            return $this->result;
        }
        //判断管理员是否存在
        $admin = $this->getById($id);
        if(!$admin){
            $this->result['status'] = -1;
            $this->result['msg'] = '管理员记录不存在';
            return $this->result;
        }

        //除了超管自己，没有人可以修改超管信息
        if($adminRedis['is_super'] != 1 && $admin['is_super'] == 1){
            $this->result['status'] = -1;
            $this->result['msg'] = '不可随意修改超级管理员信息';
            return $this->result;
        }

        //判断管理员账户是否重复
        $adminFromSql = $this->getModel('Admin')->getByWhere($pro_no,'admin_name',$reqData['admin_name'],$filed='*');
        if($adminFromSql && $reqData['admin_name'] != $admin['admin_name']){
            $this->result['status'] = -1;
            $this->result['msg'] = '该账户名已重复';
            return $this->result;
        }
        //判断手机号是否重复
        $adminFromSql = $this->getModel('Admin')->getByWhere($pro_no,'phone',$reqData['phone'],$filed='*');
        if($adminFromSql && $reqData['phone'] != $admin['phone']){
            $this->result['status'] = -1;
            $this->result['msg'] = '该手机号已重复';
            return $this->result;
        }



        //判断密码是否可修改
        if($adminRedis['is_super']!=1 && $adminSession['admin_no'] != $admin['admin_no'] && $reqData['password'] != ''){
            $this->result['status'] = -1;
            $this->result['msg'] = '不可随意修改他人密码';
            return $this->result;
        }else{
            if($reqData['password'] != ''){
                //验证密码
                if(!preg_match('/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{8,16}$/',$reqData['password'])){
                    $this->result['status'] = -1;
                    $this->result['msg'] = '请设置包含英文及数字的8～16位字符密码';
                    return $this->result;
                }
                $reqData['password'] = md5($reqData['password']);
            }else{
                unset($reqData['password'] );
            }
        }

        $reqData['updated_at'] = time();




        if($reqData['permissions'] != null){
            $reqData['permissions'] = '0,'.implode(',',$reqData['permissions']).',0';
        }else{
            $reqData['permissions'] = '0';
        }


        //更新admin
        $this->getModel('Admin')->updateById($id,$reqData);


        //redis
        $redisData = [
            'id'=>$id,
            'account'=>$reqData['admin_name'],
            'permissions'=>$reqData['permissions'],
            'is_power'=>$reqData['is_power'],
            'is_super'=>0,
        ];
        $key = $adminSession['pro_no'].":".$admin['admin_no'];
        $this->getBussiness('RedisCache')->createAdmin($key,$redisData);



        //记录日志
        $sql['pro_no'] = $adminSession['pro_no'];
        $sql['pro_name'] = $project['pro_name'];
        $sql['table'] = 'wallet_system_project_admin';
        $sql['ip'] = $_SERVER['REMOTE_ADDR'];
        $sql['admin_no'] = $adminSession['admin_no'];
        $sql['admin_name'] = $adminSession['account'];
        $sql['created_at'] = $sql['updated_at'] = time();
        $sql['sql_type'] = 'update';
        $sql['log_title'] = $adminSession['account'].'编辑了'.$admin['admin_name'].'的管理员信息';
        $sql['sql'] = $this->di->get('profiler')->getLastProfile()->getSQLStatement();
        $this->getModel('AdminLog')->add($sql);




        $this->result['status'] = 1;
        $this->result['msg'] = '编辑成功';
        return $this->result;

    }

//    public function editPowerAdmin($reqData){
//        $admin = $this->getByWhere($reqData['pro_no'],'admin_name',$reqData['admin_name']);
//        if(!$admin){
//            $this->result['status'] = -1;
//            $this->result['msg'] = '管理员记录不存在';
//            return $this->result;
//        }
//
//        $updateData = [
//            'is_power'=>$reqData['is_power'],
//            'is_super'=>$reqData['is_super'],
//        ];
//        $this->getModel('Admin')->updateById($admin['id'],$updateData);
//
//
//        //redis
//        $redisData = [
//            'is_power'=>$reqData['is_power'],
//            'is_super'=>$reqData['is_super'],
//        ];
//        $key = $reqData['pro_no'].":".$admin['admin_no'];
//        $this->getBussiness('RedisCache')->createAdmin($key,$redisData);
//
//        $this->result['status'] = 1;
//        $this->result['msg'] = '编辑成功';
//        $this->result['data'] = $key;
//        return $this->result;
//    }


}