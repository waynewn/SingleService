#!/bin/bash
if [ $# != 1 ] ; then 
echo "USAGE: $0 IP-listen" 
echo " e.g.: $0 127.0.0.1" 
exit 1; 
fi 

curl "http://"$1":8007/shutdownThisNode"
echo ""
curl "http://$1:8008/shutdownThisNode"
echo ""
curl "http://$1:8009/shutdownThisNode"
echo ""
curl "http://$1:8010/shutdownThisNode"
echo ""
./ServicesSample/Ini/bin/start.sh $1 8009
echo ""
./ServicesSample/HelloWorld/bin/start.sh $1 8010
echo ""
./ServicesSample/Email/bin/start.sh $1 8008
echo ""
./ServicesSample/UsrDatCache/bin/start.sh $1 8007
echo ""
