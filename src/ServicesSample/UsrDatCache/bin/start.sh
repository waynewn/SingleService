#!/bin/bash
cd `dirname $0`
php run.php UsrDatCache $1 $2 "http://127.0.0.1:9002/ini/broker/getini?name="

