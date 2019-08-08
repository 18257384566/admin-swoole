<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class Project extends BaseBussiness
{
    public function getDetail($pro_no){
        return $this->getModel('Project')->getDetail($pro_no,$field='*');
    }


    public function add($data){
        $result = $this->getDetail($data['pro_no']);
        if($result){
            $this->result['status'] = -1;
            $this->result['msg'] = '项目已存在';
            return $this->result;
        }

        $add = $this->getModel('Project')->add($data);
        if(!$add){
            $this->result['status'] = -1;
            $this->result['msg'] = '添加失败';
            return $this->result;
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '添加成功';
        return $this->result;
    }
}