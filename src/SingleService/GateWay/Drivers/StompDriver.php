<?php
namespace SingleService\GateWay\Drivers;
/**
 * 注意：stomp里act是接收的ack，不是处理的，即：1-5条数据，ack3了以后，1-3都ack了，只有4，5还没有
 * 需要的配置格式：server=tcp://localhost:61613&user=&pass=
 */
class StompDriver extends \SingleService\GateWay\Driver{

    /**
     * 加入队列（注意是否是消费过的消息）
     * @param string $queName 一次只能获取一个队列里的数据
     * @param json-string $array (如果不是数组，会被自动转换成array('data'=>$data))
     * @param bool $handled 是否已经处理过了
     * @return \SingleService\Ret
     */
    public function appendData($queName, $array,$handled=false)
    {
        //$this->localTrace("activeMq sendData start");
        $this->ensureConnection();
        $ret = $this->handler->send($queName,$array);
        if($ret){
            return \SingleService\Ret::factoryOk();
        }else{
            return \SingleService\Ret::factoryError($this->handler->error());
        }
    }

    protected $handler = null;
 
    protected function ensureConnection($forceReconnect=false)
    {
        //$this->localTrace("activeMq ensureConnection start");
        if($this->handler===null || $forceReconnect){
            //try {
                //$this->localTrace("activeMq reconnect。。。。。。。{$this->arrIni['server']},{$this->arrIni['user']},{$this->arrIni['pass']}");
                $this->handler = new \Stomp($this->arrIni['server'],$this->arrIni['user'],$this->arrIni['pass']);
//            } catch(\StompException $e) {
//                die('Connection failed: ' . $e->getMessage());
//            }
        }else{
            
        }
        //$this->localTrace("activeMq ensureConnection end");
    }
    /**
     * 准备获取哪些队列的数据
     */
    public function beforeHandleData($arrQues)
    {
        $this->localTrace("activeMq beforeHandleData start");
        $this->ensureConnection();
        if(is_array($arrQues)){
            foreach($arrQues as $s){
                $this->handler->subscribe($s);
            }
        }else{
            $this->handler->subscribe($arrQues);
        }
        $this->_arrQues = $arrQues;
        $this->localTrace("activeMq beforeHandleData end");
    }
    protected $_arrQues;
    /**
     * 本轮获取结束，清理相关资源
     */
    public function afterHandleData()
    {
        $this->localTrace("activeMq endHandleData start");
        if(is_array($this->_arrQues)){
            foreach($this->_arrQues as $s){
                $this->handler->unsubscribe($s);
            }
        }else{
            $this->handler->unsubscribe($arrQues);
        }
        $this->localTrace("activeMq endHandleData end");
    }

    /**
     * 取出消息（不等待），交由callback处理，最多处理$limit条，最少0条
     * @param type $limit
     * @return array 
     */
    public function getUnhandledDataArray($limit=10)
    {
        $this->localTrace("activeMq handle start");
        $this->ensureConnection();
        $ret = array();
        for($i=0;$i<$limit;$i++){
            if($this->handler->hasFrame()){
                
                $data = new \SingleService\GateWay\QueData();
                $tmp = $this->handler->readFrame();
                $data->queName = substr($tmp->headers['destination'],7);
                
                $data->queData = $tmp->body;
                $data->driverData = $tmp;
                $ret[]=$data;
                continue;
            }else{
                $this->localTrace("readframe[$i] = null (que is empty)");
                break;
            }
        }
        $this->localTrace("activeMQ handle end,total read data $i");
        return $ret;
    }
    /**
     * 确认结果，并清理data结构中相关资源
     * @param \SingleService\GateWay\QueData $data
     */
    public function confirmAddFreeData($data) {
        $this->localTrace("activeMq confirm start data:". var_export($data,true));
        if($data->handled){
            $ret = $this->handler->ack($data->driverData);
        }
        $data->driverData = null;
        $this->localTrace("activeMq confirm end=". var_export($ret,true)." data:". var_export($data,true));
        return $ret;
    }
    /**
     * 关闭连接，释放资源
     */
    public function closeAndFree()
    {
        $this->localTrace("activeMq closeAndFree start");
        $t = $this->handler;
        $this->handler=null;
        unset($t);
        $this->localTrace("activeMq closeAndFree end");
        
    }
}