<?php
return array(
//最大异步任务数
    'MAILSERVICE_MAX_TASK'=>0,
//最大同时接收请求数量（同步异步都算）
    'MAILSERVICE_MAX_REQUEST'=>10,
//module & ctrl 名称定义    
    'SERVICE_MODULE_NAME'=>'UsrDatCache',
    //需要加载所有配置
    'NeedsMoreIni'=>'DB,UsrDatMap',
//在redis里保存的时候的前置字符串
    'prefix'=>'CachedUsrData',
//指出数据库配置里的位置
    'dbIdentifier'=>'redis',
);

