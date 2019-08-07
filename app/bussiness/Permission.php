<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class Permission extends BaseBussiness
{
    public function getByNameTopId($name,$top_id){
        return $this->getModel('ProjectPermission')->getByNameTopId($name,$top_id,$field='*');
    }


    public function add($data){
        $Permission = $this->getByNameTopId($data['name'],$data['top_id']);
        if($Permission){
            return $Permission['id'];
        }
        $data['created_at'] = $data['updated_at'] = time();
        $result = $this->getModel('ProjectPermission')->add($data);

        //redis 新增权限
        $data['id'] = $result;
        $this->getBussiness('RedisCache')->addPermissions($data);

        if(!$result){
            $this->result['status'] = -1;
            $this->result['msg'] = '权限异常';
            return $this->result;
        }
        return $result;
    }

    //判断控制器权限
    public function getPermission($permissionName){
        $admin = $this->session->get('backend');
        $pro_no = $this->dispatcher->getParam('pro_no');
        if(!$admin){
            $this->functions->alert('尚未登陆','/'.$pro_no.'/login');
        }

        //获取redis
        $adminRedis = $this->getBussiness('RedisCache')->getAdmin($admin['pro_no'].':'.$admin['admin_no']);
        if($adminRedis['is_power'] != 1 && $adminRedis['is_super'] != 1){
            if(strpos($adminRedis['permissions'],','.$permissionName.',') == false){
                $this->functions->alert('很抱歉，您没有这个操作权限，如有疑问，请联系超级管理员','/'.$admin['pro_no'].'/index');
            }
        }

        return $adminRedis;
    }

    public function getSession(){
        $admin = $this->session->get('backend');
        return $admin;
    }

}