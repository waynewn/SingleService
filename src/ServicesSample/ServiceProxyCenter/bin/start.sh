#!/bin/bash 
cd `dirname $0`
#需要
php run.php ServiceProxyCenter $1 $2 `pwd`"/../_config"
#
#php run.php ServiceProxyCenter $1 $2 "http://127.0.0.1:9002/ini/broker/getini?name="
