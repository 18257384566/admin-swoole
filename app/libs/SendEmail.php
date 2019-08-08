<?php

namespace App\Libs;

use App\Libs\SMTP;

class SendEmail
{
    function send_mail($reqData)
    {
        $url = 'http://api.sendcloud.net/apiv2/mail/send';
        $API_USER = 'Influence';
        $API_KEY = 'UTVXm08H9XVz1MRW';

        $param = array(
            'apiUser' => $API_USER, # 使用api_user和api_key进行验证
            'apiKey' => $API_KEY,
            'from' => 'hello@influencechain.org', # 发信人，用正确邮件地址替代
            'fromName' => 'Incpay',
            'to' => $reqData['email'],# 收件人地址, 用正确邮件地址替代, 多个地址用';'分隔
            'subject' => $reqData['title'],
            'html' => $reqData['content'],
            'respEmailId' => 'true'
        );

        $data = http_build_query($param);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
            ));
        $context = stream_context_create($options);
        $result = file_get_contents($url, FILE_TEXT, $context);

        return $result;

    }
}