<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:28
 */

namespace App\Controllers;



class AnalyController extends ControllerBase
{

    public function registerViewAction(){
        $this->view->pick('analy/register');
    }

    public function registerQueryAction(){
        $start_time = $this->request->getQuery('start_time');
        $end_time = $this->request->getQuery('end_time');
        $type = $this->request->getQuery('type');

        if(!isset($start_time) || $start_time == ''){
            $start_time = date('Y-m-d');
        }

        if(!isset($end_time) || $end_time == ''){
            $end_time = date('Y-m-d');
        }

        if(!isset($type) || $type == ''){
            $type = 'day';
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        //查询数据
        if($type == 'day'){
            $sql = "select count(id) as `count`,`date` from homepage_user where `time` >= $start_time and `time` <= $end_time group by `date`;";
            $result=$this->db->query($sql);
            $result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $result = $result->fetchAll();
        }elseif($type == 'month'){
            $sql = "select count(id) as `count`,`month` as `date` from homepage_user where `time` >= $start_time and `time` <= $end_time group by `month`;";
            $result=$this->db->query($sql);
            $result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
            $result = $result->fetchAll();
        }

        //结果处理
        if($result == []){
            $data['analy'] = [];
            $data['long'] = 0;
        }else{
            $data['analy'] = $result;
            $data['long'] = ceil(max($result)['count'] / 5);
        }
//exit;
        $this->view->data = $data;
        $this->view->pick('analy/register');



//        exit;

    }


}