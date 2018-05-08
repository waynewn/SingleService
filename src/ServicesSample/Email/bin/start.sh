#!/bin/bash
cd `dirname $0`
cd ../../..
php run.php Email $1 $2 "http://127.0.0.1:8009/ini/broker/getini?name="

