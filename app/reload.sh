#!/bin/bash

#平滑重启单个交易对的单个进程
#调用方式：  sh reload.sh （进程名） (单个进程的名字：充值: DepositDb    提现: WithdrawDb)

pid=`pidof swoole_match_$1`
#echo $pid
kill $pid
echo "$1进程正在重启"