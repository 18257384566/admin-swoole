<?php

namespace App\Controllers;


use App\Libs\Dun163;

class IndexController extends ControllerBase
{

    public function loginAction(){
        return $this->view->pick('index/login');
    }

    //登陆
    public function doLoginAction()
    {
        $reqData['name'] = $this->request->get('name');
        $reqData['password'] = strtolower($this->request->get('password'));
        //校验数据
        $validation = $this->paValidation;
        $validation->doLogin();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $result = $this->getBussiness('Admin')->doLogin($reqData);
        if($result['status'] != 1){
            $this->result['status'] = -1;
            $this->result['msg'] = $result['msg'];
            return json_encode($this->result);
        }

        //存入session
        $data = [
            'admin_no' => $result['data']['admin_no'],
            'account' => $result['data']['admin_name'],
            'password' => $result['data']['password'],
            'is_super' => $result['data']['is_super'],
            // bn 'expiretime' => time() + $this->config->lifetime['login'],
            'ip' => $this->functions->get_client_ip(),
        ];
        $this->session->set('backend',$data);
        $_SESSION['expiretime'] = time() + $this->config->lifetime['login'];

        $this->result['status'] = 1;
        $this->result['msg'] = $result['msg'];
        $this->result['data'] = [];
        return json_encode($this->result);

    }

    //首页
    public function indexAction(){ //echo '111';exit;
        $admin = $this->dispatcher->getParam('admin');
        $data['is_super'] = $admin['is_super'];

        $this->view->data = $data;
        $this->view->pick('index/index');
    }













    //滑块验证
    public function captchaAction(){
        $reqData = $this->request->get('validate');
        if(empty($reqData)){
            $this->result['status'] = -1;
            $this->result['msg'] = '滑块验证失败';
            return json_encode($this->result);
        }

        $dun = new Dun163();
        $dunresult = $dun->dun($reqData['validate']);
        if(!$dunresult){
            $this->result['status'] = -1;
            $this->result['msg'] = '滑块验证失败';
            return json_encode($this->result);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = '滑块验证成功';
        return json_encode($this->result);
    }

    //发送短信
    public function sendMessageAction()
    {
        $reqData['phone'] = $this->request->get('phone');
        //校验数据
        $validation = $this->paValidation;
        $validation->getLoginCode();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $message = $messages[0]->getMessage();
            $this->result['status'] = -1;
            $this->result['msg'] = $message;
            return json_encode($this->result);
        }

        $sendMessage = $this->getBussiness('Admin')->sendMessage($reqData['phone']);
        if($sendMessage['status']!=1){
            $this->result['status'] = -1;
            $this->result['msg'] = $sendMessage['msg'];
            return json_encode($this->result);
        }

        $this->result['status'] = 1;
        $this->result['msg'] = $sendMessage['msg'];
        return json_encode($this->result);
    }



    public function logoutAction(){
        $adminSession = $this->session->get('backend');
        $sqlData['pro_no'] = $adminSession['pro_no'];
        $sqlData['pro_name'] = $adminSession['pro_name'];
        $sqlData['table'] = '';
        $sqlData['ip'] = $_SERVER['REMOTE_ADDR'];
        $sqlData['admin_no'] = $adminSession['admin_no'];
        $sqlData['admin_name'] = $adminSession['account'];
        $sqlData['created_at'] = $sqlData['updated_at'] = time();
        $sqlData['sql_type'] = '';
        $sqlData['log_title'] = $adminSession['account'].'退出';
        $sqlData['sql'] = '';
        $this->getBussiness('AdminLog')->add($sqlData);

        $this->session->remove("backend");
//        return $this->response->redirect("/backend/login");
        return $this->dispatcher->forward(array(
            "controller" => "index",
            "action" => "login",
        ));
    }


    public function notFoundAction(){
        $this->result = [
            'status' => -2,
            'msg' => "Route is not found!",
            'data' => []
        ];
        $this->ajaxReturn();
    }

}

