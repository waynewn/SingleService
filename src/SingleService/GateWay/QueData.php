<?php
namespace SingleService\GateWay;

class QueData
{
    public $queName='test';//队列名
    public $queData='hello world';//队列数据（可能字符串，可能数组，甚至可能是对象，GateWay\Process\Broker有提供获取需要格式的方法）
    public $driverData;//具体采用的队列保存数据用的地方
    public $handled=false;
}