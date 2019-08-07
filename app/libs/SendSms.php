<?php

namespace App\Libs;

class SendSms
{
    function send_sms($reqData = [])
    {
        $url = 'http://www.sendcloud.net/smsapi/send';
        $templateId = $reqData['templateId'];
//        $code = $reqData['code'];
        $vars = $reqData['vars'];

        $param = array(
            'smsUser' => 'incex',
            'templateId' => $templateId,
            'msgType' => '0',
            'phone' => $reqData['phone'],
//            'vars' => '{"%code%":"'.$code.'"}'
            'vars' => $vars
        );

        $sParamStr = "";
        ksort($param);
        foreach ($param as $sKey => $sValue) {
            $sParamStr .= $sKey . '=' . $sValue . '&';
        }

        $sParamStr = trim($sParamStr, '&');
        $smskey = 'c2rJXsQvDxvmcVpwxPy5jbarwSyanbHA';
        $sSignature = md5($smskey."&".$sParamStr."&".$smskey);

//        var_dump($sSignature);


        $param = array(
            'smsUser' => 'incex',
            'templateId' => $templateId,
            'msgType' => '0',
            'phone' => $reqData['phone'],
//            'vars' => '{"%code%":"'.$code.'"}',
            'vars' => $vars,
            'signature' => $sSignature
        );

        $data = http_build_query($param);
//        echo $data;

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type:application/x-www-form-urlencoded',
                'content' => $data

            ));
        $context  = stream_context_create($options);
        $result = file_get_contents($url, FILE_TEXT, $context);

        return $result;
    }
}