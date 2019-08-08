#!/bin/sh

if [ ! -n "$1" ] ;then
echo "请输入进程名"
else
NAME=$1
echo $NAME
ID=`ps -ef | grep "$NAME" | grep -v "grep" | awk '{print $2}'`
echo $ID
echo "---------------"
for id in $ID
do
kill -15 $id
echo "killed $id"
done
echo "---------------"
fi
