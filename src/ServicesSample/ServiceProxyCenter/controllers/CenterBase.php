<?php

class CenterBase extends \SingleService\ServiceController{
    /**
     * @return \Sooh\ServiceProxy\Config\CenterConfig
     */
    protected function getCenterConfig()
    {
        return \Sooh\ServiceProxy\Config\CenterConfig::getInstance($this->_Config);
    }
    protected function updCenterConfig($newone)
    {
        \Sooh\ServiceProxy\Config\CenterConfig::setInstance($this->_Config,$newone);
    }
    /**
     * 记录管理命令的日志
     * @param array $args
     */
    protected function writeCmdLog($args,$act=null)
    {
        if($act==null){
            $act = $this->_request->getActionName();
        }
        $this->_log->app_common($act.':'. json_encode($args));
    }
    
    protected function getMyIp()
    {
        return $this->getCenterConfig()->centerIp;
    }
    protected function getMyPort()
    {
        return $this->getCenterConfig()->centerPort;
    }

    /**
     * 获取指定proxy status 结果
     * 返回 array(
     *      'http://123.234.234.2:234/ServiceProxy/proxy/status'=>array(
     *          'startup' => '2018-02-28 11:53:36',
     *          'proxyip'=>'1.2.3.4',
     *          'configVersion' => '1.0.1',
     *          'request_time' => '2018-02-28 11:54:07',
     *          'nodelist' =>  array (
     *              'payment01' => 'ping命令执行结果',
     *              'payment02' => NULL,
     *          ),
     *          'proxy' =>array(
     *              'counter'=>array(
     *                  '分钟戳'=> '目标节点IP:port'=>计数
     *              ),
     *              'error'=>array(
     *                  '问题节点IP:port'=>array('timestamp'=>最后报错时间戳，Num=>连续失败次数)
     *              ),
     *          )
     *      ),
     * )
     * @param array $ips
     * @param int $skipNodeStatus 1：skip，0：withStatus
     * @return array  
     */
    protected function getProxiesStatus($ips,$skipNodeStatus=0)
    {
        $clients = \SingleService\Coroutione\Clients::create(90);
        
        if(!is_array($ips)){
            throw new \ErrorException('ips of proxy for getProxiesStatus() should be array, given:'. var_export($ips,true));
        }
        $centerConfig = $this->getCenterConfig();
        $uri0 = '/'.$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/proxy/status?skipNodeStatus='.($skipNodeStatus?1:0);
        foreach($ips as $ip){
            if($centerConfig->proxyActive[$ip]){
                $clients->addTask($ip, $centerConfig->proxyActive[$ip],$uri0);
            }else{
                
            }
        }
        
        $ret = $clients->getResultsAndFree();
        $finalRet=array();
        foreach ($ret as $k=>$v){
            $tmp = explode('/', $k);
            $k = $tmp[2];
            if($v[0]=='{'){
                $tmp = json_decode($v);
                $tmp->CurrentRequesting = json_decode(json_encode($tmp->CurrentRequesting),true);
                $tmp->NodesStatus = json_decode(json_encode($tmp->NodesStatus),true);
                $finalRet[$k] = $tmp;
            }else{
                $finalRet[$k] = $v;
            }
        }
        return $finalRet;
    }    
    
    public function checkBeforeAction() {
        $remoteAddr = $this->_request->getServerHeader('remote_addr');
        $centerConfig = $this->getCenterConfig();
        if($remoteAddr!=$centerConfig->centerIp && $remoteAddr!='127.0.0.1' && !isset($centerConfig->proxy[$remoteAddr])){
            $this->_log->app_trace('ignore cmd:'.$this->_request->getActionName().' from '.$remoteAddr);
            $this->setReturnHttpCode(404);
            return false;
        }
        return parent::checkBeforeAction();
    }
}

