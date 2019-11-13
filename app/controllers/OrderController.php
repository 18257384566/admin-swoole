<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



use function GuzzleHttp\Psr7\str;

class OrderController extends ControllerBase
{
    //订单
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
        if(!isset($search) || $search == ''){
            $allcount = $this->db->query("select count(id) as allcount from $table");
        }else{
            $allcount = $this->db->query("select count(id) as allcount from $table where `orderId` = '$search' or `NickName` = '$search'");
        }
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `UserId`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId` from $table order by CreateTime desc limit $page,$limit";
        }else{
            $sql = "select `UserId`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId` from $table where `orderId` = '$search' or `UserId` = '$search' order by CreateTime desc limit $page,$limit";
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

    public function orderLogAddAction(){
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

            $data = json_decode($json,true);//echo '<pre>'; var_dump($data);//exit;
            if(!isset($data['properties']['order']['Complete'])){
                continue;
            }

            //判断该订单是否已经存在
            $isset = $this->getModel('Order')->getByOrderId($data['properties']['order']['Id'],$filed='id');
            if($isset){
                continue;
            }

            $Items = '';
            if(isset($data['properties']['order']['Items']) && $data['properties']['order']['Items'] != ''){
                foreach ($data['properties']['order']['Items'] as $v){
                    $Items .= $v['ItemId'].','.$v['ItemQuantity'].';';
                }
                $Items = rtrim($Items,';');
            }

            //存入数据库
            $sql = "insert into homepage_order(`UserId`,`Complete`,`Channel`,`Amount`,`CreateTime`,`Items`,`orderId`,`NickName`,`GoodsId`,`created_at`,`update_at`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $params = array(
                $data['properties']['order']['UserId'],
                $data['properties']['order']['Complete'],
                $data['properties']['channel'],
                $data['properties']['order']['Amount'],
                $data['properties']['order']['CreateTime'],
                $Items,
                $data['properties']['order']['Id'],
                $data['properties']['order']['NickName'],
                $data['properties']['order']['GoodsId'],
                $time,
                $time
            );
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/order/orderadd/excle');
    }


    //充值
    public function fetch($sql){
        $result=$this->db->query($sql);
        $result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $result = $result->fetchAll();
        return $result;
    }

    public function rechargeViewAction(){
        $start_time = $this->request->getQuery('start_time');
        $end_time = $this->request->getQuery('end_time');

        if(empty($start_time)){
            $start_time = '1997-01-01';
        }

        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
//        var_dump($start_time);
//        var_dump($end_time);

        //获取订单列表
        $limit = 10;
        $page = $this->request->get('page');
        $search = $this->request->getQuery('search');
        if(!$page){
            $page=1;
        }
        $page = ($page - 1) * $limit;

        $table = 'homepage_recharge';
        //获取总条数
        if(!isset($search) || $search == ''){
            $sql = "select count(id) as allcount from $table where `date` >= $start_time and `date` < $end_time";
            $money_sum = "select sum(`money`) as money_total from $table where `date` >= $start_time and `date` < $end_time";
        }else{
            $server_id = 'zone'.$search;
            $sql = "select count(id) as allcount from $table where `date` >= $start_time and `date` < $end_time and `server_id` = '$server_id'";
            $money_sum = "select sum(`money`) as allcount from $table where `date` >= $start_time and `date` < $end_time and `server_id` = '$server_id'";
        }
        $allcount=$this->db->query($sql);
        $allcount->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $allcount = $allcount->fetch();

        //获取当页
        if(!isset($search) || $search == ''){
            $sql = "select `user_id`,`money`,`money_type`,`server_id`,`channel`,`time`,`is_success` from $table where `date` >= $start_time and `date` < $end_time order by `date` desc limit $page,$limit";
        }else{
            $server_id = 'zone'.$search;
            $sql = "select `user_id`,`money`,`money_type`,`server_id`,`channel`,`time`,`is_success` from $table where `date` >= $start_time and `date` < $end_time and `server_id` = '$server_id' order by `date` desc limit $page,$limit";
        }
        $list=$this->db->query($sql);
        $list->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $list = $list->fetchAll();


        //获取服务器
        $sql = "select `server_name`,`diserver_id`,`diserver_name` from homepage_server order by created_at desc limit $page,$limit";
        $server=$this->db->query($sql);
        $server->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $server = $server->fetchAll();

        $money_fetch = $this->fetch($money_sum);
        if(isset($money_fetch[0]['money_total'])){
            $data['money_total'] = $money_fetch[0]['money_total'];
        }else{
            $data['money_total'] = 0;
        }

        $data['server'] = $server;
        $data['allcount']=$allcount['allcount'];
        $data['page']=$this->request->get('page');
        $data['totalpage'] = ceil($data['allcount']/$limit);
        $data['search'] = "search=$search&start_time=".date('Y-m-d',$start_time)."&end_time=".date('Y-m-d',$end_time)."&";

        $this->view->data = $data;
        $this->view->list = $list;
        $this->view->pick('order/recharge');
    }

    public function rechargeImportAction(){
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
        for ($i = 0; $i < $len_result; $i++) { //循环获取各字段值
            $json = mb_convert_encoding($result[$i][0], "UTF-8", "auto");

            //判断数据是否为空
            if(!isset($json) || $json == ''){
                continue;
            }

            $data = json_decode($json,true);
            if(!isset($data['properties'])){
                continue;
            }

            //判断该订单是否已经存在
            $isset = $this->getModel('Recharge')->getByGameOrderId($data['properties']['game_order_id'],$filed='id');
            if($isset){
                continue;
            }

            $time = strtotime($data['#time']);
            $date = date('Y-m-d',$time);

            if(!isset($data['properties']['device_id'])){
                $data['properties']['device_id'] = 0;
            }
            //存入数据库
            $sql = "insert into homepage_recharge(`user_id`,`account_id`,`device_id`,`channel`,`server_id`,`vip_level`,`level`,`money`,`game_order_id`,`SDK_order_id`,`pay_order_id`,`money_type`,`payway`,`is_success`,`type`,`time`,`date`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $params = array(
                $data['properties']['user_id'],
                $data['properties']['account_id'],
                $data['properties']['device_id'],
                $data['properties']['channel'],
                $data['properties']['server_id'],
                $data['properties']['vip_level'],
                $data['properties']['level'],
                $data['properties']['money'],
                $data['properties']['game_order_id'],
                $data['properties']['SDK_order_id'],
                $data['properties']['pay_order_id'],
                $data['properties']['money_type'],
                $data['properties']['payway'],
                $data['properties']['is_success'],
                $data['#type'],
                $time,
                $date,
            );

//            var_dump($sql);
            $this->db->query($sql, $params);
        }

        $this->functions->alert('导入成功','/order/recharge/view');
    }

}