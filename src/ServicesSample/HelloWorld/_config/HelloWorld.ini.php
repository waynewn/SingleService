<?php
return array(
    //最大异步任务数
    'SERVICE_MAX_TASK'=>2,
//最大同时接收请求数量（同步异步都算）
    'SERVICE_MAX_REQUEST'=>2,
//module & ctrl 名称定义
    'SERVICE_MODULE_NAME'=>'hello',
    //消息模板
    'NeedsMoreIni'=>'',
    
    //多少毫秒检查处理一次堆积的任务
    'Timer_Interval_MS'=>5000,
    //定时任务每次最多处理多少个（假设预留10%任务处理能力缓冲，公式？？= 最大接收请求数量*0.9 * 平均任务执行耗时秒数 * 多少秒检查一次）
    'Task_Per_Interval'=>100,//0表示不处理
    //本节点服务器处理哪些消息队列（逗号分割的队列名称）
    'QueNameList'=>'Email',
    //处理事件消息时，如果错误，向这里发送消息{queName},{queData},{errMsg}是三个模板参数
    'MQReportError'=>'http://127.0.0.1:7001/evtgw/mq/add',

);
