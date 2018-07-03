<?php
return array(
    //最大异步任务数
    'SERVICE_MAX_TASK'=>3,
//最大同时接收请求数量（同步异步都算）
    'SERVICE_MAX_REQUEST'=>3,
//module & ctrl 名称定义
    'SERVICE_MODULE_NAME'=>'hello',
    'Timer_Interval_MS' => 0,
    'NeedsMoreIni'=>'Msg,KVObj,DB',
    //消息模板
    'hello_msg_tpl'=>'hi, %s',

);