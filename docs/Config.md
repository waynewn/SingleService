# 关于配置的说明

代码中可以通过
config->getIni("a.b.c")获取相应配置
另外，可以通过 Config->getRuntime('SoohCurServModName') 获取当前的实际模块名

提供了快捷方式获取模块专属配置，举例来说，对于HelloWorld模块，下面三种写法等价

$this->_Config->getIni("HelloWorld.abc")
$this->_Config->getIni($this->_Config->getRuntime('SoohCurServModName').".abc")
$this->_Config->getMainModuleConfigItem('abc')

==================>改变路由方案，不需要active&deactive操作，重新加载配置就好

## 主配置

每个微服务有个主配置文件，文件名同模块名（重申：不是服务名），主配置至少包含：

* 最大异步任务数： SERVICE_MAX_TASK， 如果没有异步task需求，这里给0
* 最大同时接收请求数量：SERVICE_MAX_REQUEST
* 服务名（不是模块名）： SERVICE_MODULE_NAME，逗号分割，根据需要修改，用于避免跟现有的冲突，以及强制版本升级
* 需要额外加载的其他配置，NeedsMoreIni，英文逗号分割列出所需的其他配置，*是Ini用的，表示加载找到的所有配置
* Timer_Interval_MS ：多少毫秒触发一次定时任务(AsyncTaskDispatcher->onTimer), =0表示不需要定时任务，<0 表示临时暂停定时任务(恢复时改回>0,重载配置，注意不是立即触发ontimer)
* Config的permanent存储：PermanentForConfig，默认的IniPermanent（除系统占用了一个，其他只能再存9个长度小于100的字符串），如果有需要，可以通过这里指定自定义的

## 启动获取和重载

启动时需要指定获取配置文件的位置，建议：

- ini启动时通过指定本地路径方式找需要的配置文件
- 其他服务启动时建议通过向Ini服务索取配置

每个服务实例内置了个重启和获取当前配置的方法（限制里必须是本机发起的命令）

- /SteadyAsHill/broker/reloadConfig 重新加载刷新配置文件（部分无效，比如上面提到的SERVICE_MAX_TASK和SERVICE_MAX_REQUEST， 以及其他在Server启动时使用的参数）
- /SteadyAsHill/broker/dumpConfig 当前配置一览

## config 使用

config (Sooh/Ini)

它的runtime在每接收到一个请求的时候会被清空，然后设置：

* SoohCurServModName 当前项目的模块名

它的permanent，是简单的数组管理的，没有使用共享内存之类的，所以只能保存不频繁更新，不怕冲突覆盖的：

* permanent->sets('shuttingDown',true)  当收到shutdown命令时，会设置


## 配置文件支持两种格式

**php版(文件名后缀php)：**

        <?php
        // 这里可以写注释
        return array(
            //这里也可以写注释
            'SERVICE_MAX_TASK'=>0,   

            'NeedsMoreIni'=>'Xyz,Wsx',//这里也可以写注释

            //多级配置
            'more'=>[
                'a'=>1, 
                'b'=>2,
                'list'=>['flg1','flg2']
            ]
            .....
        );

**ini版(文件名后缀ini)**

        ;这里可以写注释
        MAILSERVICE_MAX_TASK = 0
        MAILSERVICE_MAX_REQUEST="Xyz,Wsx"

        ;多级配置
        [more]
        a   = 1
        b  = 2
        list[]=flg1
        list[]=flg2

##支持一级子目录##

即,以下两种方式等价：

方式一：

config/DB.ini

		[mysql]
		server = 127.0.0.1
		.....

		[redis]
		server = 127.0.0.1
		.....

方式二：

config/DB/mysql.ini

		server = 127.0.0.1
		.....

config/DB/redis.php
		
		<?php
		return array(
			'server'=>'127.0.0.1',
			......
		);

