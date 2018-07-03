<?php
//加载自己的autoload
//if(is_readable('autoload.php')){
    //注意，默认的模板文件里需要指定两个路径，一个是Sooh2的，一个是公司类库的
//    include 'autoload.php';
//}
define ('SoohServiceProxyUsed','ServiceProxy');//如果工作与 Sooh-ServiceProxy 环境，这里指出对应配置的模块名
require '../../autoload.php';//如果autoload里没有相关路由自动加载，include这个
//usage: 
//php run.php HelloWorld $1 $2 'http://127.0.0.1:9002/ini/broker/getini?name='
//      0         1       2  3   4
if($argc!=5){
    die('usage: php run.php modulename ip port url-for-getIni');
}

$sys = \SingleService\Server::factory()
        ->initServiceModule(dirname(dirname(__DIR__)),$argv[1]) //本次启动的是哪个微服务
        ->initLog(
                \Sooh\Loger::getInstance()->setTraceLevel(7)
                    //->initFileTpl($basedir, $filenameWithSubDir)  // 可以使用变量：{year},{month},{day},{hour},{minute},{second},{type}
                ) //可以自定义你自己的日志输出类
        ->initSuccessCode('code', 10000, 'message', 'success')//设置默认的返回值中code,message的名称和成功时默认值
        ->initRawdataDigname('data')//rawdata里data节点的值直接提到上一级，作为request可以直接访问的
        ->initView(new \SingleService\View())//这里可以放上自定义的兼容view处理类
        ->initConfigPath($argv[4])//获取配置文件的路径或url
        ->initAutloadLocalLibrary(true);//自动加载所有模块内的library



$sys->run($argv[2], $argv[3]);
