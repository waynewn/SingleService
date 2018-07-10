#!/bin/bash
CENTER_PORT=`ps ax |grep Center|grep Ssl | awk '{print $9}'`
curl -s "http://127.0.0.1:{$CENTER_PORT}/ServiceProxy/center/nodeActive?nodes="$1 | jq .
