<?php
return array(
    //最大异步任务数
    'MAILSERVICE_MAX_TASK'=>0,
//最大同时接收请求数量（同步异步都算）
    'MAILSERVICE_MAX_REQUEST'=>10,
//module & ctrl 名称定义
    'SERVICE_MODULE_NAME'=>'hello',
    //消息模板
    'hello_msg_tpl'=>'hi, %s',
    'NeedsMoreIni'=>'Msg,KVObj,DB',
);