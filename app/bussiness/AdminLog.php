<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class AdminLog extends BaseBussiness
{
    public function add($data){
        return $this->getModel('AdminLog')->add($data);
    }



}