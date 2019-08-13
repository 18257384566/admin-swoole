<?php

namespace App\Libs;

use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;

class Functions implements InjectionAwareInterface
{
    protected $_di;

    public function setDI(DiInterface $di)
    {
        $this->_di = $di;
    }

    public function getDI()
    {
        return $this->_di;
    }

    //创建uid
    public function createNo($num = 8)
    {
        $string = "1234567890";
        $return_str = '';
        $i = 0;
        do {
            $i++;
            $return_str .= $string[mt_rand(0, 9)];
        } while ($i < $num);

        return $return_str;
    }

    //生成唯一字符串组合
    function uniqueString($num = 18)
    {
        $string = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $return_str = '';
        $i = 0;
        do {
            $i++;
            $return_str .= $string[mt_rand(0, 61)];
        } while ($i < $num);

        return $return_str;
    }

    //加密
    function encryptPassword($password = '', $saltPassword = '')
    {
        return hash('sha256', $saltPassword . $password);
    }



    //获取当前用户ip
    function getIp() {
        $unknown = 'unknown';
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        /**
         * 处理多层代理的情况
         * 或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
         */
        if (false !== strpos($ip, ',')) $ip = reset(explode(',', $ip));
        $ipinf = $this->getIpInfo($ip);
//            if ($ipinf['city'] =='内网IP'){
//                $ipinf['city'] = '上海市';
//            }
        return $ipinf;
    }

    function getIpInfo($ip){
        $url='http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
//        $url='http://ip.taobao.com/service/getIpInfo.php?ip=169.235.24.133';
        $result = file_get_contents($url);
        $result = json_decode($result,true);
        if($result['code']!==0 || !is_array($result['data'])) return false;
        return $result['data'];
    }

    //获取代理 IP
    function get_client_ip()
    {
        $unknown = 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        /**
         * 处理多层代理的情况
         * 或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
         */
        if (false !== strpos($ip, ',')){
            $ipArr = explode(',', $ip);
            $ip = reset($ipArr);
            return $ip;
        }
        return $ip;
    }

    //字符串加密
    function encrypt($data, $key)
    {
        $key = md5($key);
        $x = 0;
        $char = "";
        $str = "";
        $len = strlen($data);
        $l = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= $key{$x};
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
        }
        return base64_encode($str);
    }

    //字符串解密
    function decrypt($data,$key)
    {
        $key = md5($key);
        $x = 0;
        $char = "";
        $str = "";
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }

    //参数校验加密
    function paramSign($params)
    {
        return true;
        $sign = $params['sign'];
        unset($params['api_secret']);
        unset($params['sign']);
        ksort($params);
        //$string = http_build_query($params);
        $string = $this->paramsToString($params);
        $new_sign = md5($string);
        //dd($new_sign);
        if($sign != $new_sign){
            return false;
        }
        return true;
    }

    //params => string
    function paramsToString($params){
        $string = '';
        foreach ($params as $k=>$v){
            $string .= $k.'='.$v.'&';
        }
        return rtrim($string,'&');
    }


    //生成验证码(自定义长度)
    function GetfourStr($len)
    {
        $chars_array = array(
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
        );
        $charsLen = count($chars_array) - 1;
        $outputstr = "";
        for ($i=0; $i<$len; $i++)
        {
            $outputstr .= $chars_array[mt_rand(0, $charsLen)];
        }
        return $outputstr;
    }

    function alert($msg, $url = NULL)
    {
        header("Content-type: text/html; charset=utf-8");
        $alert_msg = "alert('$msg');";
        if (empty($url)) {
            $gourl = 'history.go(-1);';
        } else {
            $gourl = "window.location.href = '{$url}'";
        }
        echo "<script>$alert_msg $gourl</script>";
        exit;
    }

    function http_request_forWallet($url,$type = "POST", $keysArr = array(), $headersArr = array(),$timeOut = 30){
        //$headersArr['User-Agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36';
        if($type == "POST"){
            $ch = curl_init();
            if (stripos($url, "https://") !== false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
            }

            $aPOST = array();
            foreach ($keysArr as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = implode("&", $aPOST);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            //带上headers
            if($headersArr){
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArr);
            }


            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
            curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $strPOST);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);

            $sContent = curl_exec($ch);
            $aStatus = curl_getinfo($ch);

            curl_close($ch);
            if (intval($aStatus["http_code"]) == 200) {
                if($sContent){
                    return json_decode($sContent,true) ;
                }
                return $sContent;
            }
        }else{
            $url = combineURL($url, $keysArr);

            $ch = curl_init();
            if (stripos($url, "https://") !== FALSE) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            if($headersArr){
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArr);
            }

            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
            curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);


//            // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
//            curl_setopt($ch, CURLOPT_HEADER, true);
//            // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
//            curl_setopt($ch, CURLOPT_NOBODY, true);
//            //至关重要，CURLINFO_HEADER_OUT选项可以拿到请求头信息
//            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
            $sContent = curl_exec($ch);

//            if ($error == curl_error($ch)) {
//                var_export($error);
//                return false;
//            }

            $aStatus = curl_getinfo($ch);

//            echo "<pre>";
//            var_export($sContent);
//            var_export($aStatus);
//            exit;

            curl_close($ch);

//        var_dump(intval($aStatus["http_code"]));exit;

            if (intval($aStatus["http_code"]) == 200) {
                if($sContent){
                    return json_decode($sContent,true) ;
                }
                return $sContent;
            }
        }
    }


    function mergeArr($array){
        if(!is_array($array)){
            return '$array不是数组';
        }
        $list = array();
        foreach ($array as $key => $value) {
            $list = array_merge($list,$value);
        }
        return $list;
    }

    public function uri(){
        $uri = urldecode(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );
        if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
            return false;
        }

        require_once __DIR__.'/public/index.php';
    }

    public function numberFormat($num,$decimail='8'){
        return number_format($num,$decimail,'.','');
    }

    function input_csv($handle) {
        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 1000,",")) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++) {
                $out[$n][$i] = $data[$i];
            }
            $n++;
        }
        return $out;
    }

    function httpPost($url,  $keys = [], $headers = [],$cookie = '')
    {
        $headers = array('Content-Type: application/x-www-form-urlencoded');
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $keys);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch , CURLOPT_COOKIE , $cookie);
        var_dump(curl_error($ch));
        $output = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if ($output === false) { echo '11';
            return false;
        }
        if (intval($status["http_code"]) != 200) { echo '22';
            return false;
        }
        return $output;
    }

    function httpGet($url,$keys = [],$headers = [],$cookies = '')
    {
        $url = $this->combineURL($url,$keys);
        $ch = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch , CURLOPT_COOKIE , $cookies);
        $output = curl_exec($ch);
        $status = curl_getinfo($ch); //var_dump($status);//var_dump($output);
        curl_close($ch);
        if ($output === false) {
            return false;
        }
        if (intval($status["http_code"]) != 200) {
            return false;
        }
        return $output;
    }

    function combineURL($baseURL, $keysArr)
    {
        if (empty($keysArr) || !is_array($keysArr)) return $baseURL;
        $combined = $baseURL . "?";
        $valueArr = array();
        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=" . urlencode($val);
        }
        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);
        return $combined;
    }

    function http_request_code($url,$type = "POST", $keysArr = array(), $headersArr = array(),$cookie = '',$timeOut = 30){
        if($type == "POST"){
            $ch = curl_init();
            if (stripos($url, "https://") !== false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
            }

            $aPOST = array();
            foreach ($keysArr as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = implode("&", $aPOST);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            //带上headers
            if($headersArr){
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArr);
            }


            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
            curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $strPOST);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);

            $sContent = curl_exec($ch);
            $aStatus = curl_getinfo($ch);

            curl_close($ch);
            if (intval($aStatus["http_code"]) == 200) {
                if($sContent){
                    return json_decode($sContent,true) ;
                }
                return $sContent;
            }else{
                return false;
            }
        }else{
            $url = $this->combineURL($url, $keysArr);
            $ch = curl_init();
            if (stripos($url, "https://") !== FALSE) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            if($headersArr){
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArr);
            }

            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
            curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);

            $sContent = curl_exec($ch);

            $aStatus = curl_getinfo($ch); //var_dump($sContent);var_dump($aStatus);

            curl_close($ch);
            if (intval($aStatus["http_code"]) == 200) {
                if($sContent){
                    return json_decode($sContent,true) ;
                }
                return $sContent;
            }else{
                return false;
            }
        }
    }

    /**
     * 创建文件夹
     * $param string $dir /apps/cache/logs
     * $param int $mode 0777
     * return boolean
     */
    function mkdirs($dir, $mode)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
        if (!self::mkdirs(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode);
    }

}