<?php
//加载自己的autoload
if(is_readable('autoload.php')){
    include 'autoload.php';
}

include 'SingleService/Server.php';//如果autoload里没有相关路由自动加载，include这个
//usage: 
//php run.php HelloWorld $1 $2 'http://127.0.0.1:9002/ini/broker/getini?name='
//      0         1       2  3   4
if($argc!=5){
    die('usage: php run.php modulename ip port url-for-getIni');
}

$loger = 

$sys = \SingleService\Server::factory()
        ->initServiceModule(__DIR__.'/ServicesSample',$argv[1]) //本次启动的是哪个微服务
        ->initLog(
                \SingleService\Loger::getInstance(7)
                    //->initFileTpl($basedir, $filenameWithSubDir)
                ) //可以自定义你自己的日志输出类
        ->initSuccessCode('code', 10000, 'message', 'success')//设置默认的返回值中code,message的名称和成功时默认值
        ->initRawdataDigname('data')//rawdata里data节点的值直接提到上一级，作为request可以直接访问的
        ->initConfigPath($argv[4]);//获取配置文件的路径或url
\Sooh2\Misc\Ini::getInstance($sys->getConfig());//如果要使用另一数据库封装库Sooh2，打开这里
$sys->run($argv[2], $argv[3]);
