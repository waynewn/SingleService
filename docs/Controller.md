# Controller 详细说明

## 属性

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
getCookie(key=null)
getServerHeader(key=null)
setParam(key) 在request上设置自定义值（随request传递）
getParam(key, value) 在request上取出自定义值

当rawdata提交json格式参数时，反串行化后的根节点也可以通过get方式获得。另外，通过入口文件的run里调用server->initRawdataDigname('data')后，get()可以获得data节点下的数据（注意：此时如果出现重名节点，数据会丢失）

### 成员属性 this->_view

主要方法

assign(k, v)

### 成员属性 this->_Config

主要方法

getIni('a.b.c')
getRuntime('a.b.c')

*因目前没对语言文字做相应封装处理，这里可以把文字当ini管理*

### 成员属性 this->_serverOfThisSingleService

主要方法

createSwooleTask($taskName,$taskData,$funcCallback);

创建执行一个异步任务，taskname是AsyncTaskDispatcher里的函数名，data是会被当参数传入的，callback是回调函数

关于异步任务，参看 [框架说明](docs/Framework.md)里关于AsyncTaskDispather的说明。



## 函数

### 成员函数 getModuleConfigItem(subitem)

提供了快捷方式获取模块专属配置，举例来说，对于HelloWorld模块，下面三种写法等价

$this->_Config->getIni("HelloWorld.abc")
$this->_Config->getIni($this->_Config->getRuntime('CurServModName').".abc")
$this->getModuleConfigItem('abc')


### 成员函数 checkBeforeAction()

在执行action之前调用做些检查（比如，登入检查），返回bool：是否继续执行action。

* 重载checkBeforeAction()可以做些本controller专用的检查处理逻辑（别忘了parent::checkBeforeAction()）

* 可以在library目录下创建一个\Plugins\Plugin类，这个类的方法是全局所有controller的
 
附实现机制如下：

		//server 调用 action:

        if($ctrl->checkBeforeAction()){ //这里会尝试调用Plugin的 checkBeforeAction()
            $ctrl->xxxxAction();
            $ctrl->doAfterAction(true);//这里会尝试调用Plugin的doAfterAction()
        }else{
            $ctrl->doAfterAction(false);//这里会尝试调用Plugin的doAfterAction()
        }


		//ctrl里调用 plugin的checkBeforeAction的机制

        if(class_exists('\\Plugins\\Plugin',false)){
            $this->_plugin = call_user_func('\\Plugins\\Plugin::factory',$this->_request,$this->_view,$this->_Config,$this->_log);
            return $this->_plugin->checkBeforeAction();
        }else{
            return true;
        }



### 成员函数 doAfterAction($actionExecuted)

action之后，需要执行的。

checkBeforeAction()那里，如果发现了\Plugins\Plugin类，这里会执行该plugin的doAfterAction($actionExecuted)


### 成员函数 setReturnRedirect($newLocation)

设置重定向地址

###  成员函数 setReturnHttpCode($code)

设置http-code（比如404）



### 成员函数 setReturnOK($msg)

设置成功情况下返回的message

### 成员函数 setReturnError($msg, $code=null)

设置失败情况下返回的code和message

* 默认返回是成功（就算不调用这个函数，也会有成功的数据）
* 返回时节点是叫code还是其他什么，成功的默认值是0还是其他，是在run.php入口那里通过->initSuccessCode('code', 10000, 'message', 'success')设置的






