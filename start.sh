#!/bin/sh

#启动脚本: sh start.sh secondtransfertopurse transfer(进程参数)
#启动脚本: sh start.sh TransferToPurse transfer(进程参数)

#nohup /usr/local/php/bin/php run $1 $2 > `dirname $(pwd)/$0`/$1_$(date "+%Y%m%d%H%M%S").log &
nohup /usr/local/php/bin/php run secondtransfertopurse transfer > `dirname $(pwd)/$0`/secondtransfertopurse_$(date "+%Y%m%d%H%M%S").log &

#nohup /usr/local/php/bin/php run $1 $2 > `dirname $(pwd)/$0`/$1_$(date "+%Y%m%d%H%M%S").log &
nohup /usr/local/php/bin/php run TransferToPurse transfer > `dirname $(pwd)/$0`/TransferToPurse_$(date "+%Y%m%d%H%M%S").log &
#echo "$1进程启动成功"
echo "进程启动成功"
