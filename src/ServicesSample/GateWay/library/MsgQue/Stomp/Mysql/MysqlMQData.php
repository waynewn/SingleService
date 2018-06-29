<?php
namespace GWLibs\MsgQue\Stomp\Mysql;

class ActiveMQData extends \GWLibs\MsgQue\Data{
    public $activeMQ;
    public $activeMQIni;
    public $data;
    public $isJson;
    /**
     * 获取数据
     */
    public function getArrayData()
    {
        if($this->isJson){
            return json_decode($this->data->body,true);
        }else{
            return $this->data->body;
        }
    }
    /**
     * 确认数据已被处理
     */
    public function ack()
    {
        if(!$this->activeMQ){
            $this->activeMQ = \Libs\MsgQue\Broker::factory($this->activeMQIni);
        }elseif(!$this->activeMQ->ensure()){
            $this->activeMQ = \Libs\MsgQue\Broker::factory($this->activeMQIni);
        }
        $this->activeMQ->ack($this->data);
        return;
    }
}