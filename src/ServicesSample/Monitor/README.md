# 监控报警中心

定位监控报警汇总转发，没有使用队列，直接转由后台task处理（比如：转而调用邮件服务发送邮件这种），然后当场返回。

* 建议后面实际负责处理的服务都支持异步
* 一个事件可以定义多个处理方案，这里可以根据需要和事件数据整合文字提示等内容
* 第一版只实现了HttpGet（转由其他服务完成实际处理），

以 发现proxy宕机为例

        'ServiceNodeIsDown'=>[//ServiceProxy 发现宕机节点
            //事件提供的数据 proxyIpPort, proxyHostname, nodeIpPort, nodename, intro, requestUri, requestSN,time, hasArg
            array(//发个邮件
                "method"=>'HttpGet',
                'url'=>'/MailService/broker/sendAsync',//启用微服务了的话，这里只要给路径就可以了
                'args' => [
                    "from"=>'from=test@test.com',
                    'to'=>'test@test.com',
                    'title'=>'发现宕机节点：{nodename} {hasArg}',
                    'content'=>"{time} ({proxyHostname}) {proxyIpPort} -> ({nodename}) {nodeIpPort} {requestUri} {hasArg} ({requestSN})"
                ],
            )
        ],