<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class OrderController extends ControllerBase
{

    public function orderAddViewAction(){
        //查询最新插入数据的时间
        $filed = 'created_at';
        $order = $this->getModel('Order')->getLast($filed);
        if(!$order){
            $order['created_at'] = 0;
        }

        //获取订单列表
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->get('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_order';
        //获取总条数
        $allcount = $this->db->query("select count(id) as allcount from $table");
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `UserId`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId` from $table order by CreateTime desc limit $page,$limit";
        }else{
            $sql = "select `UserId`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId` from $table where `orderId` = '$search' or `NickName` = '$search' order by CreateTime desc limit $page,$limit";
        }

        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();

        //返回数据
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = 'server_name='.$search.'&';

        $data['order'] = $order;
        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('order/orderAdd');
    }

    public function orderAddAction(){
        //判断上传文件是否合法
        $filename = $_FILES['file']['tmp_name'];
        $name = strstr( $_FILES['file']['name'], '.');
        if($name != '.csv' && $name != '.tsv'){
            $this->functions->alert('导入文件格式只能为csv或者tsv');
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
            $json = mb_convert_encoding($result[$i][0], "UTF-8", "auto");

            if($json == 'orderdata'){
                continue;
            }

            //判断数据是否为空
            if(!isset($json) || $json == ''){
                continue;
            }

            $data = json_decode($json,true);
            if(!isset($data['Status'])){
                continue;
            }

            //判断该订单是否已经存在
            $isset = $this->getModel('Order')->getByOrderId($data['Id'],$filed='id');
            if($isset){
                continue;
            }

            $Items = '';
            if(isset($data['Items']) && $data['Items'] != ''){
                foreach ($data['Items'] as $v){
                    $Items .= $v['ItemId'].','.$v['ItemQuantity'].';';
                }
                $Items = rtrim($Items,';');
            }

            //存入数据库
            $sql = "insert into homepage_order(`UserId`,`Complete`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId`,`created_at`,`update_at`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $params = array(
                $data['UserId'],
                $data['Complete'],
                $data['Channel'],
                $data['Amount'],
                $data['CreateTime'],
                $Items,
                $data['Id'],
                $data['NickName'],
                $data['GoodsId'],
                $time,
                $time
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/order/orderadd/excle');
    }

}