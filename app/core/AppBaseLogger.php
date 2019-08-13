<?php
/**
 * 日志处理类
 * edited by kevin
 * 2016/8/2
 */
namespace App\Core;

use Phalcon\Di;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger;

class AppBaseLogger{
    
    private static $_instance;

    /*
     * 获取单例
     * return object
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }
    

    /*
     * 禁止克隆
     */
    public function __clone(){
        trigger_error('Clone is not allowed !');
    }
    

    public function log($message, $type="INFO",$dir="default"){
        
        $dir_file = APP_PATH.Di::getDefault()->get('config')['log_path'].$dir;
        
        //以年月份作为文件夹
        Di::getDefault()->get('functions')->mkdirs($dir_file,0777);

        //以日作为文件名
        $logger = new FileAdapter($dir_file."/".date('Y-m-d').".log");
        
        //定义信息格式
        $formatter = new LineFormatter("[%type%]%message%");
        $logger->setFormatter($formatter);
        $logger->begin();
        
        //转换成大写
        $type = strtoupper($type);
        //统一加上时间
        $message = date("H:i:s").$message;
        
        switch ($type){
            case 'INFO':
                $logger->log($message,Logger::INFO);
                break;
            case 'DEBUG':
                $logger->log($message,Logger::DEBUG);
                break;
            case 'ERROR':
                $logger->log($message,Logger::ERROR);
                break;
            case 'NOTICE':
                $logger->log($message,Logger::NOTICE);
                break;
            case 'WARNING':
                $logger->log($message,Logger::WARNING);
                break;
            case 'ALERT':
                $logger->log($message,Logger::ALERT);
                break;
            case 'EMERGENCY':
                $logger->log($message,Logger::EMERGENCY);
                break;
            default :
                $logger->log($message,Logger::INFO);
                break;
        }
        
        $logger->commit();
        $logger->close();
            
    }

}