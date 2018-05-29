#!/bin/bash
cd `dirname $0`
cd ../../..
php run.php Ini $1 $2 `pwd`"/ServicesSample/_config"

