#!/bin/bash
cd `dirname $0`
pwd
php run.php Ini $1 $2 `pwd`"/../../_config"

