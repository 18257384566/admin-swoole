<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ProjectWalletBatch extends BaseBussiness
{
    public function add($data){
        return $this->getModel('ProjectWalletBatch')->add($data);
    }


    public function getById($id){
        return $this->getModel('ProjectWalletBatch')->getById($id,$field='*');
    }


    public function getByWhere($pro_no,$whereFiled,$whereData){
        return $this->getModel('ProjectWalletBatch')->getByWhere($pro_no,$whereFiled,$whereData,$filed='*');
    }
}