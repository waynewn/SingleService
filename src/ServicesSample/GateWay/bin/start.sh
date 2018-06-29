#!/bin/bash
cd `dirname $0`
#cd ../../..
#php run.php EvtGateWay $1 $2 "http://127.0.0.1:8009/ini/broker/getini?name="
php run.php GateWay $1 $2 "/root/SingleService/SingleServiceFW/src/ServicesSample/GateWay/_config"

