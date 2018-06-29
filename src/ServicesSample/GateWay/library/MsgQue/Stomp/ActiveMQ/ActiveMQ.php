<?php
namespace GWLibs\MsgQue\Stomp\ActiveMQ;

class ActiveMQ extends \GWLibs\MsgQue\Broker{
    public $handler = null;
    protected function init()
    {
    }
    public function ensureConnection($forceReconnect=false)
    {
        \GWLibs\Misc\Funcs::trace("activeMq ensureConnection start");
        if($this->handler===null || $forceReconnect){
            //try {
                \GWLibs\Misc\Funcs::trace("activeMq reconnect。。。。。。。");
                $this->handler = new \Stomp($this->arrIni['server'],$this->arrIni['user'],$this->arrIni['pass']);
//            } catch(\StompException $e) {
//                die('Connection failed: ' . $e->getMessage());
//            }
        }else{
            
        }
        \GWLibs\Misc\Funcs::trace("activeMq ensureConnection end");
    }
/**
     * 
     * @param string $queName 一次只能获取一个队列里的数据
     * @param array $array (如果不是数组，会被自动转换成array('data'=>$data))
     */
    public function sendData($queName, $array)
    {
        \GWLibs\Misc\Funcs::trace("activeMq sendData start");
        $this->ensureConnection();
        $ret = $this->handler->send($queName,$array);
        if($ret){
            return new \GWLibs\Ret();
        }else{
            return new \GWLibs\Ret($this->handler->error(),-1);
        }
        \GWLibs\Misc\Funcs::trace("activeMq sendData end");
    }
    
    public function beforeHandleData($arrQues)
    {
        \GWLibs\Misc\Funcs::trace("activeMq beforeHandleData start");
        $this->ensureConnection();
        if(is_array($arrQues)){
            foreach($arrQues as $s){
                $this->handler->subscribe($s);
            }
        }else{
            $this->handler->subscribe($arrQues);
        }
        \GWLibs\Misc\Funcs::trace("activeMq beforeHandleData end");
    }
    public function endHandleData($arrQues)
    {
        \GWLibs\Misc\Funcs::trace("activeMq endHandleData start");
        if(is_array($arrQues)){
            foreach($arrQues as $s){
                $this->handler->unsubscribe($s);
            }
        }else{
            $this->handler->unsubscribe($arrQues);
        }
        \GWLibs\Misc\Funcs::trace("activeMq endHandleData end");
    }

    public function handleData($callback_withData,$limit=10)
    {
        \GWLibs\Misc\Funcs::trace("activeMq handle start");
        $this->ensureConnection();
        for($i=0;$i<$limit;$i++){
            if($this->handler->hasFrame()){
                
                $data = new \GWLibs\MsgQue\Data();
                $tmp = $this->handler->readFrame();
                $data->fromQue = substr($tmp->headers['destination'],7);
                
                $data->strData = $tmp->body;
                call_user_func($callback_withData,$data);
                $this->handler->act($tmp);
                unset($tmp,$data);
                continue;
            }else{
                \GWLibs\Misc\Funcs::trace("readframe[$i] = null (que is empty)");
                break;
            }
        }
        \GWLibs\Misc\Funcs::trace("activeMQ handle end,total read data $i");
        return $i;
    }
    /**
     * 
     */
    public function closeAndFree()
    {
        \GWLibs\Misc\Funcs::trace("activeMq closeAndFree start");
        $t = $this->handler;
        $this->handler=null;
        unset($t);
        \GWLibs\Misc\Funcs::trace("activeMq closeAndFree end");
        
    }
}