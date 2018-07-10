<?php
return array(
//最大异步任务数
    'SERVICE_MAX_TASK'=>10,
//最大同时接收请求数量（同步异步都算）
    'SERVICE_MAX_REQUEST'=>10,
//module & ctrl 名称定义（重命名的机会）  
    'SERVICE_MODULE_NAME'=>'monitor',
    //需要加载所有配置
    'NeedsMoreIni'=>'MonitorEvt,ServiceProxy,DB',
    
    'Timer_Interval_MS'=>30000,
    
    //'QueDriver'=>'\\SingleService\\GateWay\\Drivers\\StompDriver',
    //'QueDriverIni'=>'server=tcp://localhost:61613&user=&pass=',
    'QueDriver'=>'\\SingleService\\GateWay\\Drivers\\MysqlDriver',
    'QueDriverIni'=>'dbid=DB.mysql&tbname=test.tb_que',//NeedsMoreIni里要就上DB
    
    'QueClassNamespace'=>"\\GWPEvent\\",//需要“\”结尾
    //GWPHttpForward用于指出默认的HttpForwardEvent配置文件的配置名或直接给配置的数组，注意这里写了，上面的NeedsMoreIni还要写一遍
    "GWPHttpForward"=>"MonitorEvt"
//    array(
//        'ServiceNodeIsDown'=>[
//            //事件数据 proxyIpPort, proxyHostname, nodeIpPort, nodename, intro, requestUri, requestSN,time, hasArg
//            array(
//                "method"=>'HttpGet',
//                'url'=>'/MailService/broker/sendAsync',//启用微服务了的话，这里只要给路径就可以了
//                'args' => [
//                    "from"=>'from=test@test.com',
//                    'to'=>'test@test.com',
//                    'title'=>'发现宕机节点：{nodename} {hasArg}',
//                    'content'=>"{time} ({proxyHostname}) {proxyIpPort} -> ({nodename}) {nodeIpPort} {requestUri} {hasArg} ({requestSN})"
//                ],
//            )
//        ],
//    )
);

