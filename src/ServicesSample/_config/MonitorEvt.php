<?php
return array(
    ////------------------------ServiceProxy 发现宕机节点--------------------
    'ServiceNodeIsDown'=>[
        //事件数据 proxyIpPort, proxyHostname, nodeIpPort, nodename, intro, requestUri, requestSN,time, hasArg
        array(
            "method"=>'\\SingleService\\GateWay\\Process\\HttpGetForward',
            'url'=>'/MailService/broker/sendAsync',//启用微服务了的话，这里只要给路径就可以了
            'args' => [
                "from"=>'from=test@test.com',
                'to'=>'test@test.com',
                'title'=>'发现宕机节点：{nodename} {hasArg}',
                'content'=>"{time} ({proxyHostname}) {proxyIpPort} -> ({nodename}) {nodeIpPort} {requestUri} {hasArg} ({requestSN})"
            ],
        )
    ],
    //--------------------ServiceProxy 发现宕机proxy --------------------------
    'ServiceProxyIsDown'=>[
        //事件数据 proxyIp,  foundByCenter  time
        array(
            "method"=>'\\SingleService\\GateWay\\Process\\HttpGetForward',
            'url'=>'/MailService/broker/sendAsync',//启用微服务了的话，这里只要给路径就可以了
            'args' => [
                "from"=>'from=test@test.com',
                'to'=>'test@test.com',
                'title'=>'发现宕机proxy（{proxyIp}）',
                'content'=>"{time} {proxyIpPort} is down, found by {foundByCenter}"
            ],
        )
    ],    
);

