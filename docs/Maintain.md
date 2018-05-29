# 单服务框架

建议按下面的方式把相关的shell准备好，放在项目的bin目录下。

建议脚本支持两个参数ip port参数，下面提供的写法是按这个约定写的，即执行方式是

`/path-service/bin/xxxxx.sh ip port`

注意：SteadyAsHill 是框架占用的限制本地访问控制指令用的servicename，如有冲突，联系开发改下吧 :(

## 启动脚本 start.sh

		#!/bin/bash
		cd `dirname $0`
		cd ../../..
		php run.php 模块名 $1 $2 配置文件获取方式

注：配置文件获取方式可以是绝对路径，也可以是配套的ini服务的获取配置的地址（http://host-of-ini/ini/broker/getini?name=）

## 停止脚本 stop.sh

		#!/bin/bash
		curl -s "http://$1:$2/SteadyAsHill/broker/shutdownThisNode"| jq ".message"

## 重载配置文件 reload.sh
		
		#!/bin/bash
		curl -s "http://$1:$2/SteadyAsHill/broker/reloadConfigDefault"| jq ".message"

## 心跳检查 ping.sh

		根据具体项目配置写下吧