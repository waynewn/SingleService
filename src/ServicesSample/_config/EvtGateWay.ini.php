<?php
return array(
    //最大异步任务数
    'SERVICE_MAX_TASK'=>20,
//最大同时接收请求数量（同步异步都算）
    'SERVICE_MAX_REQUEST'=>20,
//module & ctrl 名称定义
    'SERVICE_MODULE_NAME'=>'evtgw',
    //消息模板
    'NeedsMoreIni'=>'KVObj,DB,Messager',
    //多少毫秒检查处理一次堆积的任务
    'Timer_Interval_MS'=>300,
    //每次最多处理多少个（假设预留10%任务处理能力缓冲，公式= 最大任务数*0.9 * 平均任务执行耗时秒数 * 多少秒检查一次）
    'Task_Per_Interval'=>100,//0表示不处理
);
