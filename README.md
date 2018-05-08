# 单服务框架

**说明：**

* 这个框架是用来实现内部接口服务的，不是做网站的（不考虑SEO，视图、丰富路由协议等）
* http协议，如果使用rawdata，必须是json格式
* uri中  /servicename/controllername/actionname, 每个servicename对应启动一个Server
* 返回值固定json
* 基于swoole，所以，static慎用，吃不准，就不要用！！！跟xdebug冲突！！！
* Ini服务启动时通过指定本地路径方式找需要的配置文件，其他服务启动时建议通过向Ini服务索取配置
* 附带的微服务中，bin目录下的起停脚本，统一接收2个参数（ip，port），是兼容另一个项目的需求：ServiceProxy（微服务中间件：集中管理代理路由）
* 关于模块名和服务名：物理磁盘路径中的名字是模块名，url中的名字是服务名，服务名可以在配置文件中更改
* 使用了view这个词，但这个view不能用于视图，只是数据存储器，最后用来输出json的（说response更合适，只是一般已经习惯往view里执行assign了）。
* controller 调用主流程

                if($ctrl->checkBeforeAction()){
                    $ctrl->xxxAction();
                    $ctrl->doAfterAction(true);
                }else{
                    $ctrl->doAfterAction(false);
                }

## 配置文件

**配置文件支持两种格式**

php版(文件名后缀php)：

        <?php
        // 这个是注释
        return array(
            //这里可以写注释
            'MAILSERVICE_MAX_TASK'=>0,   

            'NeedsMoreIni'=>'Xyz,Wsx',//这里可以写注释

            //多级配置
            'more'=>[
                'a'=>1, 
                'b'=>2,
            ]
            .....
        );

ini版(文件名后缀ini)

        ;这个是注释
        MAILSERVICE_MAX_TASK = 0
        MAILSERVICE_MAX_REQUEST="Xyz,Wsx"

        ;多级配置
        [more]
        a   = 1
        b  = 2

**主配置**

每个微服务有个主配置文件，文件名同模块名（重申：不是服务名），主配置至少包含：

* 最大异步任务数： MAILSERVICE_MAX_TASK， 如果没有异步task需求，这里给0
* 最大同时接收请求数量，MAILSERVICE_MAX_REQUEST
* 服务名， SERVICE_MODULE_NAME，根据需要修改，避免跟现有的冲突
* 需要额外加载的其他配置，NeedsMoreIni，英文逗号分割列出所需的其他配置，*是Ini用的，表示加载找到的所有配置

默认提供的Ini服务，可以通过 /ini/broker/getini?name=Xyz,DB  这种方式获取Xyz和DB的配置

## HelloWorld

可以对照着ServiceSample目录看。

配置文件参看上面的章节准备好，然后，用模块名HelloWorld创建一个模块根目录，在其下建立子目录：

* bin， 里面放三个shell，兼容另一个ServiceProxy项目（微服务中间件：集中管理代理路由）的需求，或者放自己的shell.
* library 模块专属类的目录，启动时会预先加载一遍，不用另行include或准备autoload
* controllers 存放controller的地方，所有的controller要继承自\SingleService\ServiceController
* 如果有异步task任务的需求，同级编写名为AsyncTaskDispatcher.php的文件，里面放异步任务函数

## controller 的编写格式

        class AgentController extends \SingleService\ServiceController{
            public function sayhiAction()
            {
                $msgtpl = $this->_Config->getIni('HelloWorld.hello_msg_tpl');

                $this->setReturnMsgAndCode(sprintf($msgtpl,$this->_request->get('name')));
            }
        }

### 成员属性 this->_log

记录日志的，主要方法有：

app_common(var1, var2) 应用日志（应用访问日志）
app_trace(var1,var2) 跟踪调试用日志（应用级）
app_error(var1,var2) 错误日志（应用级）
lib_trace(var1,var2) 跟踪调试用日志（库）
lib_error(var1,var2) 错误日志（库）
sys_trace(var1,var2) 跟踪调试用日志（系统）
sys_error(var1,var2) 错误日志（系统）

两个参数中可以有一个是数组之类的，会以var_export的方式输出到日志

输出日志格式：时间信息 ## 日志信息 ## 其他自定义信息 ## 用户、环境、进程信息  ## 触发位置函数信息以及类数组信息

### 成员属性 this->_request

主要方法：

get(key) 

当rawdata提交json格式参数时，反串行化后的根节点也可以通过get方式获得。另外，通过入口文件的run里调用server->initRawdataDigname('data')后，get()可以获得data节点下的数据（注意：此时如果出现重名节点，数据会丢失）

### 成员属性 this->_view

主要方法

assign(k, v)

### 成员属性 this->_Config

主要方法

getIni

### 成员属性 this->_serverOfThisSingleService

主要方法

createSwooleTask($taskName,$taskData,$funcCallback);

创建执行一个异步任务，taskname是AsyncTaskDispatcher里的函数名，data是会被当参数传入的，callback是函数的返回值

### 成员函数 checkBeforeAction()

在执行action之前调用做些检查（比如，登入检查），返回bool：是否继续执行action。
可以在library目录下创建一个\Plugins\Plugin类，系统发现后，会实例化它，并执行它的checkBeforeAction()，实现机制如下：

        if(class_exists('\\Plugins\\Plugin',false)){
            $this->_plugin = call_user_func('\\Plugins\\Plugin::factory',$this->_request,$this->_view,$this->_Config,$this->_log);
            return $this->_plugin->checkBeforeAction();
        }else{
            return true;
        }

说明：library目录下创建一个\Plugins\Plugin类，是所有controller都会触发的，如果需要专用的，请在controller里继承checkBeforeAction()（记得检出处理parent::checkBeforeAction()的返回）

### 成员函数 doAfterAction($actionExecuted)

action之后，需要执行的。
checkBeforeAction()那里，如果发现了\Plugins\Plugin类，这里会执行该plugin的doAfterAction($actionExecuted)


### 成员函数 redirect($newLocation)

设置重定向地址

###  成员函数 setHttpCode($code)

设置http-code（比如404）

### 成员函数 redirect($newLocation)

设置重定向地址

### 成员函数 getCurl($cookie)

获得curl封装类,参数是请求时cookie的值

curl常用方法 httpGet 和 httpPost,具体参看 \SingleService\Curl

### 成员函数 setReturnMsgAndCode($msg, $code=null)

设置返回的code和message

* 默认返回是成功（就算不调用这个函数，也会有成功的数据）
* 返回时节点是叫code还是其他什么，成功的默认值是0还是其他，是在run.php入口那里通过->initSuccessCode('code', 10000, 'message', 'success')设置的

## 异步任务 swoole task 

需要评估任务数量，在模块的配置里设置最大任务数

controller 里通过 createSwooleTask($taskName,$taskData,$funcCallback)触发 （callback可以为null）

访问 模块目录下 AsyncTaskDispatcher 里 $taskName方法，参数只有一个（建议使用数组方式把相关数据一起传入）

AsyncTaskDispatcher 里要定义 onError(\ErrorException $ex,$data) 方法处理遗漏了被系统捕获的异常，该方法要注意，如果正常taskname方法会返回数据，这里处理完错误也要返回数据（因为只有有返回数据的情况下，callback才会被触发）


## 入口文件 run.php

用法

        php run.php   模块名   监听ip地址   监听端口   配置文件目录或ini服务获取配置的地址（http://host/ini/broker/getini?name=）


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
                ->initSuccessCode('code', 10000, 'message', 'success')//设置默认的返回值中code,message的名称和成功时默认值
                ->initRawdataDigname('data')//rawdata里data节点的值直接提到上一级，作为request可以直接访问的
                ->initConfigPath($argv[4]);//获取配置文件的路径或url

        //如果要使用另一数据库封装库Sooh2，需要下面两行准备好ini和loger
        \Sooh2\Misc\Loger::getInstance(\SingleService\Loger::getInstance());
        \Sooh2\Misc\Ini::getInstance($sys->getConfig());

        //服务开始监听
        $sys->run($argv[2], $argv[3]);


## 其他


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