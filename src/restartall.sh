#!/bin/bash
#重启ServiceSample下微服务的脚本，便于改完重启测试用的 
if [ $# != 1 ] ; then 
echo "USAGE: $0 IP-listen" 
echo " e.g.: $0 127.0.0.1" 
exit 1; 
fi 

killall -9 php
#   /root/vendor/steady-as-hill/SingleService/src/ServicesSample/ServiceProxyCenter/bin/start.sh 192.168.4.240 9001
#   go run /root/go/src/github.com/waynewn/ServiceProxy/proxy.go 'http://192.168.4.240:9001/ServiceProxy/center/getProxyConfig?json=1'
./ServicesSample/ServiceProxyCenter/bin/start.sh $1 9001
sleep(1)
echo ""
#go run proxy.go 'http://192.168.4.240:9001/ServiceProxy/center/getProxyConfig?json=1'

curl 'http://127.0.0.1:9001/ServiceProxy/center/nodecmd?cmd=start&node=ini01'
sleep(1)
curl 'http://127.0.0.1:9001/ServiceProxy/center/nodecmd?cmd=start&node=monitor01' &
curl 'http://127.0.0.1:9001/ServiceProxy/center/nodecmd?cmd=start&node=monitor01' &
curl 'http://127.0.0.1:9001/ServiceProxy/center/nodecmd?cmd=start&node=monitor01' &

