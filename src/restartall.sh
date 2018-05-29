#!/bin/bash
#重启ServiceSample下微服务的脚本，便于改完重启测试用的 
if [ $# != 1 ] ; then 
echo "USAGE: $0 IP-listen" 
echo " e.g.: $0 127.0.0.1" 
exit 1; 
fi 

echo ""
./ServicesSample/Ini/bin/stop.sh $1 8009
echo ""
./ServicesSample/HelloWorld/bin/stop.sh $1 8010
echo ""
./ServicesSample/Email/bin/stop.sh $1 8008
echo ""
./ServicesSample/UsrDatCache/bin/stop.sh $1 8007
echo ""
./ServicesSample/EvtGateWay/bin/stop.sh $1 8011

echo ""
./ServicesSample/Ini/bin/start.sh $1 8009
echo ""
./ServicesSample/HelloWorld/bin/start.sh $1 8010
echo ""
./ServicesSample/Email/bin/start.sh $1 8008
echo ""
./ServicesSample/UsrDatCache/bin/start.sh $1 8007
echo ""
./ServicesSample/EvtGateWay/bin/start.sh $1 8011
echo ""