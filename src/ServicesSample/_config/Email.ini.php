<?php
return array(
//最大异步任务数
    'SERVICE_MAX_TASK'=>10,
//最大同时接收请求数量（同步异步都算）
    'SERVICE_MAX_REQUEST'=>10,
//module & ctrl 名称定义
    'SERVICE_MODULE_NAME'=>'MailService',
//邮箱设置：服务器地址
    'MAILSERVICE_MAILSERVER_ADDR'=>'smtp.exmail.qq.com',
//邮箱设置：服务器端口
    'MAILSERVICE_MAILSERVER_SSLPORT'=>465,
//邮箱设置：发送者账户名
    'MAILSERVICE_SENDER_USER'=>'test@test.com',
//邮箱设置：发送者账户密码
    'MAILSERVICE_SENDER_PASSWORD'=>'123456',
);