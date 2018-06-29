# 网关（基于消息队列）

基于消息队列的思想设计开发一个简单的事件处理框架，默认提供了mysql，redis，activemq三种方式(其中，redis&mysql需要使用Sooh2库中的db部分)

允许重复投递，具体的业务处理逻辑自行解决幂等性

事件消息只会被处理一次，不论实际处理逻辑是否成功，都会被消费。采用ack模式排他，而不是标记处理是否成功

提供三个接口：

1. add 加入队列，当场不处理，等后台进程处理
2. do  不加入队列，当场处理
3. doadd  当场处理，处理完不论成功

## 系统设计

系统围绕队列名（事件名）运转，根据队列名，可以从配置的MQUsed中获知使用哪个驱动的消息队列，对应的处理类在library的CustomTasks目录下

消息事件处理类中可以再次推入新的事件：

* 外部的需要通过接口模式
* 内部的，可以\GWLibs\MsgQue\Broker::factory(\GWLibs\Misc\Funcs::getDriverForQue($queName))->sendData($queName, $queData);

设置Task_Per_Interval=0，可以临时暂停后台处理逻辑（计时器继续跑），参见AsyncTaskDispatcher的onServerStart

## 错误处理

发现错误后，除了本地写条日志，还会往报警中心（也是本系统的接口格式）

*不论处理结果是成功失败，事件消息都会被消费掉*

## 部署

** 如何部署多个不同配置的service-node实例 **

link命令生成一个，搞个配套的配置文件,然后就可以启动两个不同的服务了。


