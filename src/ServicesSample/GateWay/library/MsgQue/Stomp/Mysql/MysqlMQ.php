<?php
namespace GWLibs\MsgQue\Stomp\Mysql;

class ActiveMQ extends \GWLibs\MsgQue\Broker{
    public function ensure()
    {
        
    }
/**
     * 
     * @param string $queName 一次只能获取一个队列里的数据
     * @param array $array (如果不是数组，会被自动转换成array('data'=>$data))
     */
    public function sendData($queName, $array)
    {
        throw new \ErrorException("todo:".__CLASS__);
    }
    
    /**
     * 
     * @param string $queName
     * @param bool $ackMode
     * @param bool $waitMode
     * @return \Libs\MsgQue\Data
     */
    public function getData($queName,$ackMode=false,$waitMode=false)
    {
        throw new \ErrorException("todo:".__CLASS__);
    }
    /**
     * 
     */
    public function closeAndFree()
    {
        throw new \ErrorException("todo:".__CLASS__);
    }
}