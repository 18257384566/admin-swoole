<?php

namespace App\Libs;

class Logger{

    protected $logPath;
    protected $logId;
    protected $loopId;
    private static $_instance;

    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function setLogId($logId){
        $this->logId=$logId;
    }

    public function setLoopId($loopId,$id=''){
        $this->loopId= $loopId.'_'.$id;
    }

    public function setLogPath($logPath){
        $this->logPath = $logPath;
    }

    public function logWrite($data,$type='INFO',$log_file=''){
        if(empty($log_file)){
            $log_file = $this->logPath.'/'.date('Ymd').'.log';
        }

        $dir = dirname($log_file);
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }

        if (!is_string($data)) {
            $data = json_encode($data, 256);
        }
        $msg = '[' . date('Y-m-d H:i:s') . ' ' . date_default_timezone_get() . ']';
        $msg .= '[' . $type . ']';
        if($this->logId){
            $msg .= '[' . $this->logId . '] ';
        }
        if($this->loopId){
            $msg .= '[' . $this->loopId . '] ';
        }
        $msg .= $data;
        $msg .= PHP_EOL;
        error_log($msg, 3, $log_file);
    }
}