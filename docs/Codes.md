# 如何基于此框架编写业务代码

提供了个HelloWorld，可以参照着看下

## 目录结构

用模块名创建一个模块根目录，在其下建立子目录：

* bin， 里面放需要的shell.
* library 模块专属类的目录，启动时会预先加载一遍，不用另行include或准备autoload
* controllers 存放controller的地方，所有的controller要继承自\SingleService\ServiceController
* 如果有异步task任务的需求，同级编写名为AsyncTaskDispatcher.php的文件，里面放异步任务函数

附： 兼容ServiceProxy（微服务中间件：集中管理代理路由），需要bin下的shell接收两个参数完成起停之类的运维管理动作（格式： start.sh ip port），且需要有stdout的输出。

## 配置文件

配置文件参看 [配置文件详细说明](docs/Config.md)准备好

## controller 的编写格式

最简单的hello world

        class AgentController extends \SingleService\ServiceController{
            public function sayhiAction()
            {
                $msgtpl = $this->_Config->getIni('Msg.HelloWorld.zhcn');

                $this->returnOK(sprintf($msgtpl,$this->_request->get('name')));
            }
        }

补充说明：

系统已经替你设置过成功的返回值了，如果需要更改提示或错误，才需要调用returnOK

controller详细的属性方法，参看：[controller详细说明](docs/Controller.md)

request，view等用法，参见：[框架说明](docs/Framework.md)

## 启动用法

        php run.php   模块名   监听ip地址   监听端口   配置文件目录或ini服务获取配置的地址（http://host/ini/broker/getini?name=）

## 初次在swoole环境开发者，请注意！！！

**swoole跟xdebug冲突！！！**
**swoole跟xdebug冲突！！！**
**swoole跟xdebug冲突！！！**

基于swoole，static使用上要注意：

- 没有被预先加载的类里相关的static可以正常使用
- 所有被预加载的类里涉及的static，都会被脏读，工具类无所谓，其他情况，需要在前置里清理一下

全局变量，诸如$_GET,$_SERVER,$_REQUEST,$_COOKIE都不能用了

## 进阶

配置文件参看 [配置文件详细说明](docs/Config.md)准备好，下面就不赘述了。

### 情景一：当需要处理controller不存在的情况时

比如，需要一个拦截旧接口统一报错的情况

编写一个处理的controller类

		class MissingController extends \SingleService\ServiceController{
		    public function checkBeforeAction()
		    {
				$this->returnError('Missing',404);
				return false;
		    }
		}

然后，在入口文件run.php里，增加设置 ->initSetCtrlIfMissing('Missing')即可

### 情景二：统一增加一个检查用户是否登入的检查

在library目录下放置一个Plugin.php文件

        <?php
        namespace Plugins;

        class Plugin extends \SingleService\Plugin{
            public function checkBeforeAction()
            {
                $sessid = $this->_request->getCookie('sessid');
                if($logined){
                    return true;
                }else{
                    $this->_view->assign('loginUrl','....');
                    $this->returnError('need login',401);
                    return false;
                }
            }
        }

### 情景三：启动后台异步task进程

ctrl里

        class XXXXController extends \SingleService\ServiceController{
            public function XXXXAction()
            {
                ......
                $this->_serverOfThisSingleService->createSwooleTask('taskFuncName', $data)
                ......
            }
        }

在项目目录下放置AsyncTaskDispatcher.php(注意方法名跟前面一致)

		<?php
		class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispather{
		    public function taskFuncName($data)
		    {
		        ...........
		
		    }
		}

提醒：task是另一进程，所以像数据库连接什么的都要重新建立

可以参看样例Email（异步发送邮件）

### 情景四： 需要一个定时任务

常见于网关任务，接收任务后本地入库，后台起个定时，不停的处理记录的任务

在项目目录下放置AsyncTaskDispatcher.php，定义定时任务的处理函数，然后在onServerStart里启动一个定时任务

<?php

class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispather{

    public function onServerStart($SingleServer)
    {
        swoole_timer_tick(间隔毫秒数,function ($timer_id, $tickCounter) use ($SingleServer){

             $SingleServer->createSwooleTask('xxxxx', 参数);

        },null);

    }
    /**
     * 获取一个任务
     */
    protected function xxxxx($param)
	{
		....
	}

可以参看样例EvtGateWay（事件网关：接收事件消息入库；启动了个定时任务不停的从数据库里读取并处理）