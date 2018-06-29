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
        error_log('server start....'.$modName.':'.$xmlFile);
        try{
            $this->_Config->permanent->sets('centerConfig', $ttttt=\Sooh\ServiceProxy\Config\XML2CenterConfig::parse($xmlFile));
            error_log(var_export($ttttt,true));
        }catch(\ErrorException $ex){
            $msg = "error:".$ex->getMessage()." when parse ServiceProxy config:".$xmlFile;
            echo $msg;
            error_log($msg);
            \Sooh\Curl::getInstance()->httpGet('http://127.0.0.1:'.$swooleRequest->server['server_port'].'/SteadyAsHill/broker/shutdownThisNode');
            return;
        }
        
        swoole_timer_tick($this->_Config->getIni("$modName.Timer_Interval_MS")*60000,function ($timer_id, $tickCounter) use ($SingleServer,$modName){
                
        $curl = \Sooh\Curl::getInstance();
        $allProxy = array();
        foreach ($this->config->proxy as $s){
            $tmp = $this->getProxyConfigObjFromStr($s);
            $allProxy[$tmp->myIp]=$tmp->myPort;
        }
        
        foreach($allProxy as $proxyIp=>$proxyPort){
            $ret = $curl->httpGet("http://$proxyIp:$proxyPort/".MICRO_SERVICE_MODULENAME.'/proxy/gatherByCenter');
            $arr = json_decode($ret,true);
            if(is_array($arr)){
                foreach($arr['proxy_sum'] as $ipport=>$num){
                    $this->log->syslog('proxyCounter '.$proxyIp.' => '.$ipport.' '.$num);
                }
            }else{
                $this->log->syslog('proxyCounterMiss '.$proxyIp.' http-code:'.$curl->httpCodeLast);
            }
        }
                
        },null);

    }
    

}
