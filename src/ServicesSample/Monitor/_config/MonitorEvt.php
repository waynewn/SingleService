<?php
return array(
    'ServiceNodeIsDown'=>[//ServiceProxy 发现宕机节点
        //事件数据 proxyIp, proxyHostname, nodeIpPort, nodename, intro, requestUri, requestSN,time, hasArg
        array(
            "method"=>'HttpGet',
            'url'=>'/MailService/broker/sendAsync',//启用微服务了的话，这里只要给路径就可以了
            'args' => [
                "from"=>'from=test@test.com',
                'to'=>'test@test.com',
                'title'=>'发现宕机节点：{nodename} {hasArg}',
                'content'=>"{time} ({proxyHostname}) {proxy} -> ({nodename}) {nodeIpPort} {requestUri} {hasArg} ({requestSN})"
            ],
        )
    ],
    'ServiceProxyIsDown'=>[//ServiceProxy 发现宕机proxy
        //事件数据 proxyIp,  foundByCenter  time
        array(
            "method"=>'HttpGet',
            'url'=>'/MailService/broker/sendAsync',//启用微服务了的话，这里只要给路径就可以了
            'args' => [
                "from"=>'from=test@test.com',
                'to'=>'test@test.com',
                'title'=>'发现宕机proxy（{proxyIp}）',
                'content'=>"{time} {proxyIp} is down, found by {foundByCenter}"
            ],
        )
    ],    
);

