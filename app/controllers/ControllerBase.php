<?php

namespace App\Controllers;

use App\Bussiness\BussinessFactory;
use App\Models\ModelFactory;
use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    public $result = array(
        'status' => 1,
        'msg' => '',
        'data' => []
    );
    protected $pro_no;
    protected $encryption_security_url;

    public function initialize()
    {

    }


    public function returnArr($status=0,$msg='',$data='')
    {
        return [
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ];
    }

    public function json($arr)
    {
        if($arr){
            header('Content-type:application/json');
            exit(json_encode($arr,JSON_UNESCAPED_UNICODE));
        }else{
            exit('response data not correct!');
        }
    }


    /**
     * ajax json 输出
     * array $result
     * 一般由个的元素组成'code','msg','data'
     */
    protected function ajaxReturn(){
        $this->response->setJsonContent($this->result);
        $this->response->send();
        $this->view->disable();
        exit();
    }


    public function redirect($route){
        return Header("Location:".$route);

    }

    protected function getBussiness($bussinessName)
    {
        return BussinessFactory::getBussiness($bussinessName);
    }

    protected function getModel($modelName)
    {
        return ModelFactory::getModel($modelName);
    }
}
