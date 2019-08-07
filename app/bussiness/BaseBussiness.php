<?php

namespace App\Bussiness;

use App\Models\ModelFactory;
use Phalcon\Di\Injectable;

class BaseBussiness extends Injectable
{

    //规范化ajax输出
    protected $result = array(
        'status' => 1,
        'msg' => '',
        'data' => []
    );
    protected $pro_no;
    protected $encryption_security_url;

    public function returnArr($status=0,$msg='',$data='')
    {
        return [
            'status' => $status,
            'msg'  => $msg,
            'data' => $data
        ];
    }

    protected function ajaxReturn(){
        $this->response->setJsonContent($this->result);
        $this->response->send();
        $this->view->disable();
        exit();
    }

    protected function getModel($modelName)
    {
        return ModelFactory::getModel($modelName);
    }

    protected function getBussiness($bussinessName)
    {
        return BussinessFactory::getBussiness($bussinessName);
    }
}