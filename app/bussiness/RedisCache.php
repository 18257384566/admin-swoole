<?php

namespace App\Bussiness;

class RedisCache extends BaseBussiness
{

    //防止短时间内的重复操作
    public function redisLock($key,$time){
        if($this->redis->get($key)){
            return false;
        }else{
            $this->redis->setex($key,$time,1);
            return true;
        }

    }

    //生成钱包地址锁
    public function addUserWalletRedisLock($key){
        if($this->redis->get($key)){
            return false;
        }else{
            return $this->redis->set($key,1,3600);
        }

    }



    //权限
    public function addPermissions($data){
        return $this->redis->sAdd('permissions',json_encode($data,true));
    }

    public function getPermissions(){
        $data = $this->redis->smembers('permissions');
        if(!$data){
            return false;
        }
        return $data;
    }


    //生成验证码
    public function codeDeadline($phone,$code){
        $key = 'codeDeadline:'.$phone;
        $lifetime = 15*60;
        return $this->redis->set($key,$code,$lifetime);
    }

    //查看验证码
    public function getLoginCode($phone){
        $key = 'codeDeadline:'.$phone;
        $result = $this->redis->get($key);
        if(!$result){
            return false;
        }
        return $result;
    }

    //删除验证码
    public function delLoginCode($phone){
        $key = 'codeDeadline:'.$phone;
        return  $this->redis->del($key);
    }

    //登陆信息
    public function getLoginInfo($pro_no,$account){
        $key = $pro_no.':'.$account;
        $data = $this->redis->hGetAll($key);
        if(!$data){
            return false;
        }
        return $data;

    }

    //设置登陆信息（错误次数）
    public function setLoginInfo($pro_no,$account,$data,$lifetime){
        $key = $pro_no.':'.$account;
        $this->redis->hMset($key,$data);
        return $this->redis->EXPIRE($key,$lifetime);
    }

    //新增管理员redis
    public function createAdmin($key,$data){
        return $this->redis->hMset($key,$data);
    }

    //key是pro_no:admin_no
    public function getAdmin($key){
        $data = $this->redis->hGetAll($key);
        if(!$data){
            return false;
        }
        return $data;
    }



    //记录密码错误次数
    public function setError($key){
        $result = $this->redis->get($key);
        if(!$result){
            return false;
        }
        return $result;
    }
    //更新密码错误次数
    public function editError($key,$data,$lifetime){
        return $this->redis->set($key,$data,$lifetime);
    }

//    //项目详情
//    //编辑冷钱包输入密码
//    public function getColdWalletError($pro_no,$admin_no){
//        $key = $pro_no.":".$admin_no.':editColdWallet:passwordError';
//        $result = $this->redis->get($key);
//        if(!$result){
//            return false;
//        }
//        return $result;
//    }
//
//    public function editColdWalletError($pro_no,$admin_no,$data,$lifetime){
//        $key = $pro_no.":".$admin_no.':editColdWallet:passwordError';
//        return $this->redis->set($key,$data,$lifetime);
//    }

    //转出冷钱包错误次数
//    public function getTransferColdWalletError($pro_no,$admin_no){
//        $key = $pro_no.":".$admin_no.':transferColdWallet:passwordError';
//        $result = $this->redis->get($key);
//        if(!$result){
//            return false;
//        }
//        return $result;
//    }
//
//    public function editTransferColdWalletError($pro_no,$admin_no,$data,$lifetime){
//        $key = $pro_no.":".$admin_no.':transferColdWallet:passwordError';
//        return $this->redis->set($key,$data,$lifetime);
//    }

    //失败重转冷钱包
//    public function getSecondTransferColdWalletError($pro_no,$admin_no){
//        $key = $pro_no.":".$admin_no.':second:transferColdWallet:passwordError';
//        $result = $this->redis->get($key);
//        if(!$result){
//            return false;
//        }
//        return $result;
//    }
//
//    public function editSecondTransferColdWalletError($pro_no,$admin_no,$data,$lifetime){
//        $key = $pro_no.":".$admin_no.':second:transferColdWallet:passwordError';
//        return $this->redis->set($key,$data,$lifetime);
//    }

    //提现-错误次数
//    public function getWithdrawError($pro_no,$admin_no){
//        $key = $pro_no.":".$admin_no.':withdraw:passwordError';
//        $result = $this->redis->get($key);
//        if(!$result){
//            return false;
//        }
//        return $result;
//    }
//
//    public function editWithdrawError($pro_no,$admin_no,$data,$lifetime){
//        $key = $pro_no.":".$admin_no.':withdraw:passwordError';
//        return $this->redis->set($key,$data,$lifetime);
//    }

    //批量提现
//    public function getMoreWithdrawError($pro_no,$admin_no){
//        $key = $pro_no.":".$admin_no.':withdraw:more:passwordError';
//        $result = $this->redis->get($key);
//        if(!$result){
//            return false;
//        }
//        return $result;
//    }
//
//    public function editMoreWithdrawError($pro_no,$admin_no,$data,$lifetime){
//        $key = $pro_no.":".$admin_no.':withdraw:more:passwordError';
//        return $this->redis->set($key,$data,$lifetime);
//    }


    //转出冷钱包-存入用户钱包地址 task
    public function lpushTransferColdWallet($data){
        $listKey = 'wallet:transfer';
        if($this->redis->lpush($listKey,json_encode($data))){
            return true;
        }
        return false;
    }

    //失败重新转出冷钱包-存入用户钱包地址 task
    public function lpushSecondTransferColdWallet($data){
        $listKey = 'wallet:second:transfer';
        if($this->redis->lpush($listKey,json_encode($data))){
            return true;
        }
        return false;
    }



    //生成钱包地址，存redis
    public function addWalletAddress($address){
        return $this->redis->sAdd('wallet_center:set:wallet',$address);
    }




    //地址为key，存wallet_type
    public function addWalletAddressType($address,$data){
        return $this->redis->set($address,json_encode($data,true));
    }

    public function getEOSWalletAddress($key){
        if($this->redis->get($key)){
            $walletAddress = mt_rand(100000,999999);
            $key = 'EOS_address_'.$walletAddress;
            $this->getEOSWalletAddress($key);
        }else{
            //不存在这个key
            return true;
        }

    }

    //生成代币
    public function addCoin($data){
        return $this->redis->sAdd('wallet_center:set:coins:content',json_encode($data,true));
    }




}