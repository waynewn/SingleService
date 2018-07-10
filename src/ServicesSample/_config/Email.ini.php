<?php

return array(
//最大异步任务数
    'SERVICE_MAX_TASK'=>10,
//最大同时接收请求数量（同步异步都算）
    'SERVICE_MAX_REQUEST'=>10,
//module & ctrl 名称定义
    'SERVICE_MODULE_NAME'=>'MailService',
    
    "BySender"=>array(
        'test@test.com'=>array(
        //邮箱设置：服务器地址
            'server'=>'smtp.exmail.qq.com',
        //邮箱设置：服务器端口
            'port'=>465,
        //邮箱设置：发送者账户名
            'user'=>'test@test.com',
        //邮箱设置：发送者账户密码
            'pass'=>'123456',
        )
    ),
);