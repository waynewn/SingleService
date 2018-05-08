#!/bin/bash
curl -s "http://$1:$2/shutdownThisNode" | jq ".msg"

