# 关于框架的说明

## 核心文件说明

### SingleService/AsyncTaskDispatcher
	
异步任务的统一入口，包括:

**swoole task处理时抛出的异常的处理函数onError(\ErrorException $ex,$func,$data)**


- 参数会把异常、task函数名、data参数一并传入
- swoole的回调函数需要有返回值才能触发，所以如果有等待的回调，这里酌情考虑是否要有返回值

**server启动时的一次性事件onServerStart($SingleServer,$request)**

- 参数$SingleServer是\SingleService\Server；
- 一般这里用于启动一个定时器（swoole_timer_tick函数说明参看swoole官方手册）

		swoole_timer_tick(间隔毫秒数，function ($timer_id, $tickCounter) use ($SingleServer){
                $finished=0;
                $SingleServer->createSwooleTask('doOneJob', 参数);
        },null);

**定时任务 onTimer(server,tickCount)**

定时任务， $server 传进来是 createSwooleTask 用的 

**执行task之前调用doBeforeTask($func,$data)**

不是task的callback，不具备拦截之类的任何功效，只是用来做准备和清理的

**执行task之前调用doAfterTask($func,$data)**

不是task的callback，不具备拦截之类的任何功效，只是用来做准备和清理的

### SingleService/Config

配置管理，Server启动前初始化好，后面触发调用重载命令时可以重新加载刷新，具体参看 [配置文件详细说明](docs/Config.md)

### SingleService/Curl

curl封装，get & post 两种方法

* $ret = Curl::factory()->httpGet($url,$params_get,$headers,$secondsTimeout)
* $ret = Curl::factory()->httpPost($url,$params_post,$headers,$secondsTimeout)

其中，httpPost()中的$params_post是string时，将以raw-data方式post

### SingleService/Loger

记录日志的类

- app_common(var1, var2) 应用日志（应用访问日志）
- app_trace(var1,var2) 跟踪调试用日志（应用级）
- app_error(var1,var2) 错误日志（应用级）
- lib_trace(var1,var2) 跟踪调试用日志（库）
- lib_error(var1,var2) 错误日志（库）
- sys_trace(var1,var2) 跟踪调试用日志（系统）
- sys_error(var1,var2) 错误日志（系统）

其中 var1,var2，可以有一个不是标量（会被以var_export(xxx,true)的方式记录到日志）

- initFileTpl($basedir=null,$filenameWithSubDir=null) 自定义目录文件名

可以使用变量：{year},{month},{day},{hour},{minute},{second},{type}，但是因不会自动建立目录，所以只有{year},{type}这种可以用于目录，{hour}这种只能用于文件名

### SingleService/Plugin

提供一个全局的action前置和后置处理机制


                if($ctrl->checkBeforeAction()){ //这里会尝试调用Plugin的checkBeforeAction()
                    $ctrl->xxxxAction();
                    $ctrl->doAfterAction(true);//这里会尝试调用Plugin的doAfterAction()
                }else{
                    $ctrl->doAfterAction(false);//这里会尝试调用Plugin的doAfterAction()
                }

### SingleService/Request

request的封装，常用方法如下

- get($key) : 获取 get & post的参数
- getServerHeader($key) ：获取 server & header 里的值
- getActionName()
- getControllerName()
- getModuleName()
- getCookie($key) //setcookie在view那里

注意： raw-data方式时，会当json处理，get()获取其中根节点名字，以及server启动时指定的->initRawdataDigname('data') 的节点下的名字，有冲突会覆盖！！！

### SingleService/Server

### SingleService/ServiceController

参照yaf命名格式约定的Controller

具体参看 [Controller详细说明](docs/Controller.md)

### SingleService/View

遵循mvc习惯保留的view，但不处理view的逻辑，只是数据存储器，最后用来输出json的

*说response更合适，只是一般已经习惯往view里执行assign了*

常用方法

- assign(key, value)
- httpCodeAndNewLocation(code, redirto=null)
- setcookie($key, $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false , $httponly = false)

### run.php （入口文件）

**标准用法（启动服务）**

        php run.php   模块名   监听ip地址   监听端口   配置文件目录或ini服务获取配置的地址（比如http://host/ini/broker/getini?name=）


关于库文件加载，基本分三种情况：

1. 像composer这种，在run.php的入口开头加入相关autoload
2. 模块专属类库，启动时会全部预先加载，不需要autoload
3. 如果有其他自有库，通过server->initIncludePath()设置自动加载（需要psr-4）

代码

        <?php
        //加载自己的autoload,比如composer的autoload
        if(is_readable('autoload.php')){
            include 'autoload.php';
        }

        include 'SingleService/Server.php';//如果autoload里没有相关路由自动加载，include这个

        if($argc!=5){
            die('usage: php run.php modulename ip port url-for-getIni');
        }

        $sys = \SingleService\Server::factory()
				->initIncludePath('/path/')//设置额外自动加载路径
                ->initServiceModule(__DIR__.'/ServicesSample',$argv[1]) //本次启动的是哪个微服务
                ->initLog(
                        \SingleService\Loger::getInstance(7)
                            //->initFileTpl($basedir, $filenameWithSubDir)
                        ) //可以自定义你自己的日志输出类
                ->initView(new \SingleService\View())//这里可以放上自定义的兼容view处理类
                ->initSuccessCode('code', 10000, 'message', 'success')//设置默认的返回值中code,message的名称和成功时默认值
                ->initRawdataDigname('data')//rawdata里data节点的值直接提到上一级，作为request可以直接访问的
                ->initConfigPath($argv[4])//获取配置文件的路径或url，$permanentDriver 使用了了默认的数组驱动（在swoole下，不是实时且可能写覆盖）
                ->initWWW("/path/to/www")//设置html静态资源路径，开启会影响效率
                ;
        //如果要使用另一数据库封装库Sooh2，需要下面两行准备好ini和loger
        \Sooh2\Misc\Loger::getInstance(\SingleService\Loger::getInstance());
        \Sooh2\Misc\Ini::getInstance($sys->getConfig());

        //服务开始监听
        $sys->run($argv[2], $argv[3]);

**手动执行task任务**

调用方式

        php runtask.php   模块名   task任务函数名   task任务参数（数组的话提供json串）

复制一个run.php到runtask.php，把最后的

        $sys->run($argv[2], $argv[3]);

换成
    
        $sys->runTaskOnly($argv[2], $argv[3]);



### 其他

restartall.sh 是重启ServiceSample下微服务的脚本，便于改完重启测试用的 ；）


ServiceSample 下是几个小的Service

Email 发送邮件的
HelloWorld 演示测试用的
Ini 配置管理
UsrDatCache 缓存数据管理（支持分用户）

_config下

KVObj 这个是例子中，使用另一个数据库访问库Sooh2时用的（数据库访问封装：支持分表分库，存储no-sql型数据时，代码无差异支持mysql\redis\mongo）
演示用ServiceProxy配置： 这个是当要部署在另一个http的微服务中间件项目中，参看的配置

restartall.sh 是重启ServiceSample下微服务的脚本，便于改完重启测试 ；）

**todo**

* 消息翻译以及多语言支持
* run里关于日志文件的配置如何抽出来
