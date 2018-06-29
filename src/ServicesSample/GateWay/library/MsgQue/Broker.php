<?php
namespace GWLibs\MsgQue;
/** 
 * 消息队列代理
 */

class Broker {
    /**
     * ini : class=\a\b\c[&ip=1.2.3.4&user=abc后面这些根据具体的消息队列自行设置]
     * @return \GWLibs\MsgQue\Broker
     */
    public static function factory($ini){
        if(is_array($ini)){
            $arr = $ini;
        }elseif (is_string($ini)){
            parse_str($ini, $arr);
        }else{
            throw new \ErrorException("invalid ini given:". var_export($ini,true));
        }
        $c = $arr['class'];
        $mq = new $c;
        $mq->arrIni = $arr;
        $mq->init();
        return $mq;
    }

    public function ensureConnection($forceReconnect=false){}
    protected function init(){}
    protected $arrIni;

    /**
     * @param type $queName
     * @param array $array (如果不是数组，会被自动转换成array('data'=>$data))
     * @return \GWLibs\Ret
     */
    public function sendData($queName, $array)
    {
        throw new \ErrorException("todo:".__CLASS__);
    }
    
    /**
     * 获取并处理数据，回调函数接收一个参数（data），处理完需要ack
     * 不等待，最多取出limit个（默认10）
     * 返回本次调用实际处理了多少数据
     * @param callback $callback_withData
     * @param int $limit 最多取出多少数据
     * @return int 
     */
    public function handleData($callback_withData,$limit=10)
    {
        throw new \ErrorException("todo:".__CLASS__);
    }
    
    public function beforeHandleData($arrQues)
    {
        
    }
    public function endHandleData($arrQues)
    {
        
    }
    /**
     * 
     */
    public function closeAndFree()
    {
        throw new \ErrorException("todo:".__CLASS__);
    }
}

