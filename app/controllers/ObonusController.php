<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class ObonusController extends ControllerBase
{
    public function addViewAction(){
        return $this->view->pick('obonus/add');
    }

    public function addObonusAction(){
        header("Content-type: text/html; charset=utf-8");
        //判断是否上传文件
        if(!isset($_FILES['file'])){
            $this->functions->alert('请上传文件');
        }

        //判断上传文件是否合法
        $filename = $_FILES['file']['tmp_name'];
        $name = strstr( $_FILES['file']['name'], '.');
        if($name != '.csv'){
            $this->functions->alert('导入文件格式只能为csv');
        }
        if (empty ($filename)) {
            $this->functions->alert('请选择要导入的CSV文件');
        }

        //打开上传文件
        $handle = fopen($filename, 'r');
        $result = $this->functions->input_csv($handle); //解析csv
        $len_result = count($result);
        if($len_result == 0){
            $this->functions->alert('没有任何数据');
        }

        //遍历表格数据
        $time = time();
        for ($i = 0; $i < $len_result; $i++) { //循环获取各字段值
            $reqData['obonus_code'] = iconv('gb2312', 'utf-8', $result[$i][0]); //中文转码
            $reqData['request_num'] = iconv('gb2312', 'utf-8', $result[$i][1]);
            $reqData['item_id'] = 5;

            //判断金币是否是数据
            if(!is_numeric($reqData['request_num'])){
                continue;
            }

            //存入数据库
            $sql = "insert into homepage_obonus (`obonus_code`,`request_num`,`item_id`,`created_at`,`updated_at`) VALUES (?,?,?,?,?)";
            $params = array(
                $reqData['obonus_code'],
                $reqData['request_num'],
                $reqData['item_id'],
                $time,
                $time
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/obonus/add');
    }

    public function listAction(){
        $limit = 10;
        $page = $this->request->get('page');

        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_obonus';

        $search = $this->request->get('search');
        if(isset($search) && $search != ''){
            //获取总条数
            $allcount = $this->db->query("select count(id) as allcount from $table  where obonus_code like '$search%' order by id desc");
            $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $allcount = $allcount->fetch();

            //获取当页
            $sql = "select id,user_name,obonus_code,request_num,is_used,used_time from $table where obonus_code like '$search%' order by id desc limit $page,$limit";
            $list=$this->db->query($sql);
            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $list = $list->fetchAll();
        }else{
            //获取总条数
            $allcount = $this->db->query("select count(id) as allcount from $table");
            $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $allcount = $allcount->fetch();

            //获取当页
            $sql = "select id,user_name,obonus_code,request_num,is_used,used_time from $table order by id desc limit $page,$limit";
            $list=$this->db->query($sql);
            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $list = $list->fetchAll();
        }

        //返回数据
        $data['allcount'] = $allcount['allcount'];
        $data['page'] = $this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('obonus/list');
    }

    public function useAction(){
        $reqData['obonus_code'] = $this->request->getPost('obonus_code');
        $reqData['zones'] = $this->request->getPost('zones');
        $reqData['user_name'] = $this->request->getPost('nickname');

        //校验数据
        $validation = $this->paValidation;
        $validation->obonusUse();
        $messages = $validation->validate($reqData);
        if(count($messages)){
            $this->result['status'] = -1;
            $this->result['msg'] = $message = $messages[0]->getMessage();
            return json_encode($this->result);
        }

        $obonusUse = $this->getBussiness('Obonus')->obonusUse($reqData);
        return json_encode($obonusUse);
    }

}