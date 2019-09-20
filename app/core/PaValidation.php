<?php

namespace App\Core;


use Phalcon\Validation;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Between;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\StringLength;

class PaValidation extends Validation
{
    public function initialize()
    {

    }

    //管理员登陆
    public function doLogin(){
        $this->add('name',new PresenceOf(array(
            'message' => '请输入账户名',
            'cancelOnFail' => true,
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请输入密码',
            'cancelOnFail' => true
        )));
    }

    public function addAdmin(){
        $this->add('admin_name', new PresenceOf(array(
            'message' => '请填写账户名',
            'cancelOnFail' => true
        )));
        $this->add('admin_name', new StringLength(array(
            'max' => 20,
            'messageMaximum' => '账户名不超过20个字',
            'cancelOnFail' => true
        )));
        $this->add('real_name', new PresenceOf(array(
            'message' => '请填写真实姓名',
            'cancelOnFail' => true
        )));
        $this->add('real_name', new StringLength(array(
            'max' => 20,
            'messageMaximum' => '真实姓名不超过20个字',
            'cancelOnFail' => true
        )));
        $this->add('password', new PresenceOf(array(
            'message' => '请填写密码',
            'cancelOnFail' => true
        )));
        $this->add('password', new Regex(array(
            'pattern' => '/^(?!\d+$)(?![A-Za-z]+$)[a-zA-Z0-9]{8,16}$/',
            'message' => '请设置包含英文及数字的8～16位字符密码', //密码格式错误
            'cancelOnFail' => true
        )));
        $this->add('phone', new PresenceOf(array(
            'message' => '请填写手机号',
            'cancelOnFail' => true
        )));
        $this->add('phone', new Regex(array(
            //以非0打头的正整数
            'pattern' => '/^1[3456789]\d{9}$/',
            'message' => '手机号格式不正确',                 //手机号非法
            'cancelOnFail' => true
        )));
        $this->add('role', new PresenceOf(array(
            'message' => '请选择角色',
            'cancelOnFail' => true
        )));
    }

    public function exchange(){
        $this->add('exchange_code', new PresenceOf(array(
            'message' => '兑换券编号不能唯空',
            'cancelOnFail' => true
        )));
        $this->add('zones', new PresenceOf(array(
            'message' => 'zones不能为空',
            'cancelOnFail' => true
        )));
        $this->add('user_name', new PresenceOf(array(
            'message' => '用户昵称不能为空',
            'cancelOnFail' => true
        )));
    }

    public function getShipInfo(){
        $this->add('nickname', new PresenceOf(array(
            'message' => '请输入昵称',
            'cancelOnFail' => true
        )));
    }

    public function disable(){
        $this->add('zones', new PresenceOf(array(
            'message' => '请选择区服',
            'cancelOnFail' => true
        )));
        $this->add('user', new PresenceOf(array(
            'message' => '请填写昵称',
            'cancelOnFail' => true
        )));
        $this->add('t', new PresenceOf(array(
            'message' => '请选择封禁结束时间',
            'cancelOnFail' => true
        )));
    }

    public function userInfo(){
        $this->add('nickname', new PresenceOf(array(
            'message' => '请输入昵称',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => '请选择查询类型',
            'cancelOnFail' => true
        )));
    }

    public function notalk(){
        $this->add('zone', new PresenceOf(array(
            'message' => '请选择区服',
            'cancelOnFail' => true
        )));
        $this->add('nickname', new PresenceOf(array(
            'message' => '请填写昵称',
            'cancelOnFail' => true
        )));
        $this->add('t', new PresenceOf(array(
            'message' => '请选择封禁结束时间',
            'cancelOnFail' => true
        )));
    }

    public function noticeAdd(){
        $this->add('channel', new PresenceOf(array(
            'message' => '请选择渠道',
            'cancelOnFail' => true
        )));
        $this->add('notice', new PresenceOf(array(
            'message' => '请填写公告',
            'cancelOnFail' => true
        )));
    }

    public function noticeApi(){
        $this->add('channel', new PresenceOf(array(
            'message' => '请选择渠道',
            'cancelOnFail' => true
        )));
    }

    public function serverAdd(){
        $this->add('server_name', new PresenceOf(array(
            'message' => '请填写服务器名',
            'cancelOnFail' => true
        )));
        $this->add('url', new PresenceOf(array(
            'message' => '请填写url',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => '请选择类型',
            'cancelOnFail' => true
        )));
        $this->add('diserver_id', new PresenceOf(array(
            'message' => '请填写区服id',
            'cancelOnFail' => true
        )));
        $this->add('diserver_name', new PresenceOf(array(
            'message' => '请填写区服名',
            'cancelOnFail' => true
        )));
    }

    public function diserverAdd(){
        $this->add('server_id', new PresenceOf(array(
            'message' => '请选择服务器',
            'cancelOnFail' => true
        )));
        $this->add('diserver_id', new PresenceOf(array(
            'message' => '请填写区服id',
            'cancelOnFail' => true
        )));
        $this->add('diserver_name', new PresenceOf(array(
            'message' => '请填写区服名',
            'cancelOnFail' => true
        )));
    }

    public function onlineQuery(){
        $this->add('zones', new PresenceOf(array(
            'message' => '请选择游戏区服',
            'cancelOnFail' => true
        )));
        $this->add('t1', new PresenceOf(array(
            'message' => '选择查询时间',
            'cancelOnFail' => true
        )));
    }

    public function propServer(){
        $this->add('server_id', new PresenceOf(array(
            'message' => '请选择服务器',
            'cancelOnFail' => true
        )));
        $this->add('mailtitle', new PresenceOf(array(
            'message' => '请填写邮箱标题',
            'cancelOnFail' => true
        )));
        $this->add('mailcontent', new PresenceOf(array(
            'message' => '请填写邮箱内容',
            'cancelOnFail' => true
        )));
        $this->add('itemSelected', new PresenceOf(array(
            'message' => '请选择要发送的道具',
            'cancelOnFail' => true
        )));
        $this->add('nickname', new PresenceOf(array(
            'message' => 'nickname',
            'cancelOnFail' => true
        )));
    }

    public function obonusUse(){
        $this->add('obonus_code', new PresenceOf(array(
            'message' => '请输入用户编号',
            'cancelOnFail' => true
        )));
        $this->add('zones', new PresenceOf(array(
            'message' => '请输入区服id',
            'cancelOnFail' => true
        )));
        $this->add('user_name', new PresenceOf(array(
            'message' => '请输入用户编号',
            'cancelOnFail' => true
        )));
    }

    public function propSend(){
        $this->add('zones', new PresenceOf(array(
            'message' => '请选择区服',
            'cancelOnFail' => true
        )));
        $this->add('mailtitle', new PresenceOf(array(
            'message' => '请填写邮箱标题',
            'cancelOnFail' => true
        )));
        $this->add('mailcontent', new PresenceOf(array(
            'message' => '请填写邮箱内容',
            'cancelOnFail' => true
        )));
        $this->add('itemSelected', new PresenceOf(array(
            'message' => '请选择要发送的道具',
            'cancelOnFail' => true
        )));
        $this->add('nickname', new PresenceOf(array(
            'message' => '请填写昵称',
            'cancelOnFail' => true
        )));
    }

    public function tableSend(){
        $this->add('server_id', new PresenceOf(array(
            'message' => '请选择区服',
            'cancelOnFail' => true
        )));
        $this->add('type', new PresenceOf(array(
            'message' => '请填写标识',
            'cancelOnFail' => true
        )));
        $this->add('mailtitle', new PresenceOf(array(
            'message' => '请填写邮件标题',
            'cancelOnFail' => true
        )));
        $this->add('mailcontent', new PresenceOf(array(
            'message' => '请填写邮件内容',
            'cancelOnFail' => true
        )));
    }



}