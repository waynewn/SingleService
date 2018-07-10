<?php
/**
 * 网关任务
 *
 * @author wangning
 */
class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispatcher{
    /**
     * 创建一个定时任务：每次获取最大指定数量的任务，由异步task执行任务
     * @param \SingleService\Server $SingleServer
     */
    public function onServerStart($SingleServer)
    {
        try{
            \Sooh\ServiceProxy\Config\CenterConfig::reload($this->_Config);
            return true;
        }catch(\ErrorException $ex){
            $msg = "error : ".$ex->getMessage()." when prepare ServiceProxy config:".$this->_Config->permanent->getConfigLocation();
            throw new \ErrorException($msg);
        }
    }
    
    protected function onTimer($server)
    {
        $centerHostName = gethostname();
        $centerConfig = \Sooh\ServiceProxy\Config\CenterConfig::getInstance($this->_Config);
        $curl = \Sooh\Curl::getInstance();
        $allProxy = array();
        foreach ($centerConfig->proxy as $s){
            $tmp = \Sooh\ServiceProxy\Config\XML2CenterConfig::getProxyConfigObjFromStr($s,$centerConfig);
            $allProxy[$tmp->myIp]=$tmp->myPort;
        }
        
        foreach($allProxy as $proxyIp=>$proxyPort){
            $ret = $curl->httpGet("http://$proxyIp:$proxyPort/".$this->_Config->getMainModuleConfigItem('SERVICE_MODULE_NAME').'/proxy/gatherByCenter')->body;
            $arr = json_decode($ret,true);
            if(is_array($arr)){
                foreach($arr['proxy_sum'] as $ipport=>$num){
                    $this->_log->app_trace('proxyCounter '.$proxyIp.' => '.$ipport.' '.$num);
                }
            }else{
                $evtData = array('foundByCenter'=>$centerHostName,'proxyIpPort'=>$proxyIp.':'.$proxyPort,'time'=>date('m-d H:i:s'));
                \Sooh\Curl::getInstance()->httpGet($centerConfig->monitorConfig->service,array('evt'=>'ServiceProxyIsDown','data'=>json_encode($evtData)));
                //$server->createSwooleTask();
                $this->_log->app_trace('proxyCounterMiss '.$proxyIp.' http-code:'.$curl->httpCodeLast);
            }
        }
    }
}
