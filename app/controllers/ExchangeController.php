<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class ExchangeController extends ControllerBase
{
    public function addViewAction(){
        return $this->view->pick('exchange/add');
    }

    public function addExchangeAction(){
        header("Content-type: text/html; charset=utf-8");
        //判断是否上传文件
        if(!isset($_FILES['file'])){
            $this->alert('请上传文件');
        }

        //判断上传文件是否合法
        $filename = $_FILES['file']['tmp_name'];
        $name = strstr( $_FILES['file']['name'], '.');
        if($name != '.csv'){
            $this->alert('导入文件格式只能为csv');
        }
        if (empty ($filename)) {
            $this->alert('请选择要导入的CSV文件');
        }

        //打开上传文件
        $handle = fopen($filename, 'r');
        $result = $this->functions->input_csv($handle); //解析csv
        $len_result = count($result);
        if($len_result == 0){
            $this->alert('没有任何数据');
        }

        //遍历表格数据
        $time = time();
        for ($i = 0; $i < $len_result; $i++) { //循环获取各字段值
            $reqData['exchange_code'] = iconv('gb2312', 'utf-8', $result[$i][0]); //中文转码
            $reqData['card_no'] = iconv('gb2312', 'utf-8', $result[$i][1]);

            //存入数据库
            $sql = "insert into homepage_exchange (`exchange_code`,`card_no`,`created_at`,`updated_at`) VALUES (?,?,?,?)";
            $params = array(
                $reqData['exchange_code'],
                $reqData['card_no'],
                $time,
                $time
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/exchange/add');
    }

    public function listAction(){
        $limit = 10;
        $page = $this->request->get('page');

        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_exchange';

        $search = $this->request->get('search');
        if(isset($search) && $search != ''){
            //获取总条数
            $allcount = $this->db->query("select count(id) as allcount from $table  where exchange_code like '$search%' or uid like '$search%' or user_name like '$search%'");
            $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $allcount = $allcount->fetch();

            //获取当页
            $sql = "select id,exchange_code,card_no,is_used,uid,used_time,user_name from $table where exchange_code like '$search%' or uid like '$search%' or user_name like '$search%' order by created_at desc limit $page,$limit";
            $list=$this->db->query($sql);
            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $list = $list->fetchAll();
        }else{
            //获取总条数
            $allcount = $this->db->query("select count(id) as allcount from $table");
            $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $allcount = $allcount->fetch();

            //获取当页
            $sql = "select id,exchange_code,card_no,is_used,uid,used_time,user_name from $table order by created_at desc limit $page,$limit";
            $list=$this->db->query($sql);
            $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $list = $list->fetchAll();
        }

        //返回数据
        $data['allcount'] = $allcount['allcount'];
        $data['page'] = $page;
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = '';

        $this->view->list = $list;
        $this->view->data = $data;
        $this->view->pick('exchange/list');
    }

}