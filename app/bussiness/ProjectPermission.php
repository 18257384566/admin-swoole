<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ProjectPermission extends BaseBussiness
{
    public function getAll(){
        return $this->getModel('ProjectPermission')->getAll($field='*');
    }

    public function getByTopId($top_id){
        return $this->getModel('ProjectPermission')->getByTopId($top_id,$field='*');
    }

}