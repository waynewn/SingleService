<?php
/**
 * 网关任务
 *
 * @author wangning
 */
class AsyncTaskDispatcher extends \SingleService\AsyncTaskDispather{
    /**
     * 创建一个定时任务：每次获取最大指定数量的任务，由异步task执行任务
     * @param \SingleService\Server $SingleServer
     */
    public function onServerStart($SingleServer,$swooleRequest)
    {
        $modName = $this->_Config->getRuntime('CurServModName');
        $this->_Config->permanent->sets('locationOfXML',$xmlFile = $this->_Config->getIni("$modName.LocOfServiceProxyXML"));
        try{
            $ttttt=\Sooh\ServiceProxy\Config\XML2CenterConfig::parse($xmlFile);
            $ttttt->envIni['MICRO_SERVICE_MODULENAME']=$this->getModuleConfigItem('SERVICE_MODULE_NAME');
            \Sooh\ServiceProxy\Config\CenterConfig::setInstance($this->_Config, $ttttt);
        }catch(\ErrorException $ex){
            $msg = "error:".$ex->getMessage()." when parse ServiceProxy config:".$xmlFile;
            echo $msg;
            error_log($msg);
            \Sooh\Curl::getInstance()->httpGet('http://127.0.0.1:'.$swooleRequest->server['server_port'].'/SteadyAsHill/broker/shutdownThisNode');
            return;
        }
        
    }
    
    protected function onTimer($server,$tickCounter)
    {
        $centerConfig = \Sooh\ServiceProxy\Config\CenterConfig::getInstance($this->_Config);
        $curl = \Sooh\Curl::getInstance();
        $allProxy = array();
        foreach ($centerConfig->proxy as $s){
            $tmp = \Sooh\ServiceProxy\Config\XML2CenterConfig::getProxyConfigObjFromStr($s,$centerConfig);
            $allProxy[$tmp->myIp]=$tmp->myPort;
        }
        
        foreach($allProxy as $proxyIp=>$proxyPort){
            $ret = $curl->httpGet("http://$proxyIp:$proxyPort/".$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/proxy/gatherByCenter')->body;
            $arr = json_decode($ret,true);
            if(is_array($arr)){
                foreach($arr['proxy_sum'] as $ipport=>$num){
                    $this->_log->app_trace('proxyCounter '.$proxyIp.' => '.$ipport.' '.$num);
                }
            }else{
                $this->_log("TODO error report on proxy down");
                //$server->createSwooleTask();
                $this->_log->app_trace('proxyCounterMiss '.$proxyIp.' http-code:'.$curl->httpCodeLast);
            }
        }
    }
}
