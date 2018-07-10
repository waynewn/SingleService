<?php
namespace SingleService\GateWay;

abstract  class Driver {
    protected static $_instance=null;
    /**
     * 
     * @param type $config
     * @return \SingleService\GateWay\Driver
     */
    public static function getInstance($config=null)
    {
        if($config!=null){
            $class  = $config->getMainModuleConfigItem('QueDriver');
            self::$_instance = new $class;
            parse_str($config->getMainModuleConfigItem('QueDriverIni'),$arrConfig);
            if(is_array($arrConfig)){
                self::$_instance->initConfig($arrConfig,$config);
            }else{
                throw new \ErrorException("ini-string invalid:".$config->getMainModuleConfigItem('QueDriverIni'));
            }
            
        }
        if(empty(self::$_instance)){
            throw new \ErrorException("MQ-driver not inited");
        }
        return self::$_instance;
    }
    
    protected $arrIni;
    protected function initConfig($queConfig,$config)
    {
        //$this->localTrace("init config:".json_encode($arrConfig));
        $this->arrIni = $queConfig;
    }
    protected function localTrace($str)
    {
        error_log("MQ-Driver-trace>>$str");
    }
    /**
     * 加入队列（注意是否是消费过的消息）
     * @param string $queName 一次只能获取一个队列里的数据
     * @param json-string $array (如果不是数组，会被自动转换成array('data'=>$data))
     * @param bool $handled 是否已经处理过了
     * @return \SingleService\Ret
     */
    abstract  public function appendData($quename,$quedata,$handled=false);
    /**
     * 准备获取哪些队列的数据
     */
    abstract public function beforeHandleData($arrQues);
    /**
     * 本轮获取结束，清理相关资源
     */
    abstract public function afterHandleData();
    /**
     * 取出消息（不等待），交由callback处理，最多处理$limit条，最少0条
     * @param type $limit
     * @return array
     */    
    abstract public function getUnhandledDataArray($limit=10);

    /**
     * 关闭连接，释放资源
     */    
    abstract public function closeAndFree();
    /**
     * 确认结果，并清理data结构中相关资源
     * @param \SingleService\GateWay\QueData $data
     */    
    abstract public function confirmAddFreeData($data);
}