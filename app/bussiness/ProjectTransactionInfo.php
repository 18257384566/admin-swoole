<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class ProjectTransactionInfo extends BaseBussiness
{
    public function add($data){
        return $this->getModel('ProjectTransactionInfo')->add($data);
    }

    public function getByWhere($pro_no,$whereFiled,$whereData){
        return $this->getModel('ProjectTransactionInfo')->getByWhere($pro_no,$whereFiled,$whereData,$filed='*');
    }

    public function updateByWhere($data,$whereFiled,$whereData){
        return $this->getModel('ProjectTransactionInfo')->updateByWhere($data,$whereFiled,$whereData);
    }
}