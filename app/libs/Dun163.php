<?php

namespace App\Libs;
use function var_dump;

define("YIDUN_CAPTCHA_API_VERSION", "v2");
define("YIDUN_CAPTCHA_API_TIMEOUT", 5);
define("YIDUN_CAPTCHA_API_URL", "http://c.dun.163yun.com/api/v2/verify");

class Dun163
{
    private $captchaId = '659ba10af0634d2d9451f90fdcadb336';
    private $secretId = '89f13f34a1a99a48746efbde4ecdd3b6';
    private $secretKey = '33251a8ec8076b9f08634cede60f360d';

    /**
     * 发起二次校验请求
     * @param $validate 二次校验数据
     */
    public function dun($Validate)
    {

        $params = array();
        $params["captchaId"] = $this->captchaId;
        $params["validate"] = $Validate;
        $params["user"] = '';
        // 公共参数
        $params["secretId"] = $this->secretId;
        $params["version"] = YIDUN_CAPTCHA_API_VERSION;
        $params["timestamp"] = sprintf("%d", round(microtime(true) * 1000));// time in milliseconds
        $params["nonce"] = sprintf("%d", rand()); // random int
        $params["signature"] = $this->gen_signature($this->secretKey, $params);

        $result = $this->send_http_request($params);
        return array_key_exists('result', $result) ? $result['result'] : false;
    }


    /**
     * 生成签名信息
     * $secretKey 产品私钥
     * $params 接口请求参数，不包括signature参数
     */
    function gen_signature($secretKey,$params){
        ksort($params);
        $buff="";
        foreach($params as $key=>$value){
            $buff .=$key;
            $buff .=$value;
        }
        $buff .= $secretKey;
        return md5(mb_convert_encoding($buff, "utf8", "auto"));
    }

    /**
     * 发送http请求
     * @param $params 请求参数
     */
    private function send_http_request($params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, YIDUN_CAPTCHA_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, YIDUN_CAPTCHA_API_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, YIDUN_CAPTCHA_API_TIMEOUT);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        /*
         * Returns TRUE on success or FALSE on failure.
         * However, if the CURLOPT_RETURNTRANSFER option is set, it will return the result on success, FALSE on failure.
         */
        $result = curl_exec($ch);
        // var_dump($result);

        if (curl_errno($ch)) {
            $msg = curl_error($ch);
            curl_close($ch);
            return array("error" => 500, "msg" => $msg, "result" => false);
        } else {
            curl_close($ch);
            return json_decode($result, true);
        }
    }
}