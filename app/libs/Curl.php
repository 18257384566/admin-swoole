<?php

namespace App\Libs;

class Curl{

    /**
     * 服务器通过get请求获得内容
     * @param  string $baseURL 基础URL
     * @param  array $keysArr 请求参数
     * @return string          [description]
     */

    const Time_Out = 10;

    public function httpPost($url, $postData)
    {
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt( $ch , CURLOPT_TIMEOUT, self::Time_Out);
        $output = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if ($output === false) {
            return false;
        }

        if (intval($status["http_code"]) != 200) {
            return false;
        }
        return $output;
    }

    public static function curl_get($baseURL, $keysArr = array()){
        $url = self::combineURL($baseURL, $keysArr);
        $ch = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch , CURLOPT_TIMEOUT, self::Time_Out);
        $sContent = curl_exec($ch);

        if($error=curl_error($ch)){
            die($error);
        }

        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    /**
     * post方式请求资源
     * @param string $url       基于的baseUrl
     * @param array $keysArr    请求的参数列表
     * @return string           返回的资源内容
     */
    public static function curl_post($url, $keysArr = array()){
        $ch = curl_init();
        if(stripos($url,"https://") !== false){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }

        $aPOST = array();
        foreach($keysArr as $key=>$val){
            $aPOST[] = $key."=".urlencode($val);
        }
        $strPOST =  implode("&", $aPOST);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POST,true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$strPOST);
        curl_setopt( $ch , CURLOPT_TIMEOUT, self::Time_Out);
        $sContent = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    /**
     * combineURL
     * 拼接url
     * @param string $baseURL   基于的url
     * @param array  $keysArr   参数列表数组
     * @return string           返回拼接的url
     */
    public static function combineURL($baseURL, $keysArr){
        if(empty($keysArr) || !is_array($keysArr)) return $baseURL;

        $combined = $baseURL."?";
        $valueArr = array();
        foreach($keysArr as $key => $val){
            $valueArr[] = "$key=".urlencode($val);
        }
        $keyStr = implode("&",$valueArr);
        $combined .= ($keyStr);
        return $combined;
    }





}