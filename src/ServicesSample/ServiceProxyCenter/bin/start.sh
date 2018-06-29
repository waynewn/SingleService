#!/bin/bash
cd `dirname $0`
pwd
php run.php ServiceProxyCenter $1 $2 `pwd`"/../../_config"

