<?php
require_once __DIR__.'/CenterBase.php';

class CenterController extends \CenterBase{
    /**
     * 通知节点执行某个命令
     */
    public function nodecmdAction()
    {
        $cmd = array(
            'nodename'=>$this->_request->get('node'),
            'nodecmd'=>$this->_request->get('cmd'),
        );
        $this->writeCmdLog($cmd);
        $centerConfig = $this->getCenterConfig();
        if(!isset($centerConfig->nodeLocation[$cmd['nodename']])){
            $this->setReturnError('node with name:'.$cmd['nodename'].' not found', 404);
        }else{
            $proxyIp = $cmd['nodeip']= $centerConfig->nodeLocation[$cmd['nodename']]['ip'];
            $cmd['nodeport']=$centerConfig->nodeLocation[$cmd['nodename']]['port'];
            $proxyPort = $centerConfig->proxyActive[$proxyIp];
        }
        //$this->log->trace("============>$proxyIp:$proxyPort {$cmd['nodename']} {$cmd['nodecmd']}");
        $clients = \SingleService\Coroutione\Clients::create(120);
        $clients->addTask($proxyIp, $proxyPort, '/'.$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/proxy/nodecmd?'. http_build_query($cmd), null);
        $this->_log->app_trace('send cmd: http://'.$proxyIp.':'.$proxyPort.'/'.$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/proxy/nodecmd?'. http_build_query($cmd));
        $ret0 = current($clients->getResultsAndFree());
        $ret = json_decode($ret0,true);
        //todo: 根据命令，决定配置更新：启动停止

        if(is_array($ret)){
            foreach($ret as $k=>$v){
                $this->_view->assign($k, $v);
            }
            $this->setReturnOK();
        }else{
            $this->setReturnOK("cmd ret not received at ".date('H:i:s'));
        }
        
    }
    /**
     * dump 当前的路由（节点）情况
     */
    public function dumpServiceMapAction()
    {
        $this->writeCmdLog(array());
        $centerConfig = $this->getCenterConfig();
        $this->_view->assign('serviceMap', $centerConfig->getServiceMap());
        $this->_view->assign('deactived', $centerConfig->serviceMapDeactive);
        $this->setReturnOK();
    }
    /**
     * proxy启动时向center索要自己的配置文件
     */
    public function getProxyConfigAction()
    {
        $remoteAddr = $this->_request->getServerHeader('remote_addr');
        $this->writeCmdLog(array('proxyip'=>$remoteAddr));
        $centerConfig = $this->getCenterConfig();
        if(empty($centerConfig)){
            $this->setReturnHttpCode(404);
            return;
        }
        $proxyStr = $centerConfig->proxy[$remoteAddr];
        if(empty($proxyStr)){
            $this->setReturnHttpCode(404);
        }else{
            $tmp = \Sooh\ServiceProxy\Config\XML2CenterConfig::getProxyConfigObjFromStr($proxyStr,$centerConfig);
            $arr = json_decode($tmp->toString(true),true);
            foreach($arr as $k=>$v){
                $this->_view->assign($k, $v);
            }
        }
    }
    /**
     * 重新加载xml配置（但不下发通知）
     */    
    public function reloadConfigAction()
    {
        $this->writeCmdLog(array());
        try{
            $tmp = \Sooh\ServiceProxy\Config\XML2CenterConfig::parse($this->_Config->permanent->gets('locationOfXML'));
        }catch(\ErrorException $ex){
            $tmp = null;
        }
        if(!empty($tmp)){
            $centerConfig = $this->getCenterConfig();
            $centerConfig->copyFrom($tmp);
            $this->updCenterConfig($centerConfig);
            $this->setReturnOK('new config version is: '.$centerConfig->configVersion);
        }else{
            $this->setReturnError(empty($ex)?"parse xml failed":$ex->getMessage());
        }
    }
    /**
     * 通知所有的proxy 节点更新配置
     */
    public function broadcastAction()
    {
        $this->writeCmdLog(array(),'broadcast');
        $centerConfig= $this->getCenterConfig();
        $clients = \SingleService\Coroutione\Clients::create(5);
        $ret = array('time_start'=>date('m-d H:i:s',$this->_request->getServerHeader('request_time')),'time_end'=>'');
        foreach($centerConfig->proxy as $proxyStr)
        {
            $tmp = \Sooh\ServiceProxy\Config\XML2CenterConfig::getProxyConfigObjFromStr($proxyStr,$centerConfig);
            $str  = json_encode(array(
                'code'=>0,
//                'data'=>$tmp->toString(),
                'json'=>$tmp->toString(true)
            ));
            $clients->addTask($tmp->myIp, $tmp->myPort, '/'.$this->getModuleConfigItem('SERVICE_MODULE_NAME').'/proxy/updateConfig', $str);

        }
        $ret['result']=$clients->getResultsAndFree();
            
        $ret['time_end']= date('m-d H:i:s');
        foreach ($ret as $k=>$v){
            $this->_view->assign($k, $v);
        }
        $this->setReturnOK();
    }
    
    /**
     * 临时关闭某(些)节点,并广播到各个proxy (这里没考虑两个管理员同时操作，一个关闭一个开启，会冲突)
     * 参数： nodes： 逗号分割的节点名称
     */
    public function nodeDeactiveAction()
    {
        $centerConfig = $this->getCenterConfig();
        $nodes0 = $this->_request->get('nodes');
        $this->writeCmdLog(array('nodes'=>$nodes0));
        if(empty($nodes0)){
            return $this->setReturnError('at least one node needs given');
        }
        $nodes = explode(',',$nodes0);
        $nodeIpPort = array();
        foreach($nodes as $nd){
            if(isset($centerConfig->nodeLocation[$nd])){
                $r = $centerConfig->nodeLocation[$nd];
                $nodeIpPort[$r['ip'].':'.$r['port']] = $r;
            }else{
                return $this->setReturnError('node '.$nd.' not found');
            }
        }
        $oldServiceMap = $centerConfig->getServiceMap();
        
        foreach($oldServiceMap as $serviceName=>$byType){
            $tmp = array();
            foreach($byType as $routeType=>$rs){
                foreach ($rs as $r){
                    $s = $r['ip'].':'.$r['port'];
                    if(isset($nodeIpPort[$s])){
                        $centerConfig->serviceMapDeactive[$serviceName][$routeType][]=$r;
                    }else{
                        $tmp[$routeType][]=$r;
                    }
                }
            }
            $oldServiceMap[$serviceName]=$tmp;
        }
        $centerConfig->setServiceMap($oldServiceMap);
        $this->updCenterConfig($centerConfig);
        $this->broadcastAction();
        $this->setReturnOK($nodes0.' deactived');
    }
    /**
     * 恢复某(些)被临时关闭的节点,并广播到各个proxy(这里没考虑两个管理员同时操作，一个关闭一个开启，会冲突)
     * 参数： nodes： 逗号分割的节点名称
     */
    public function nodeActiveAction()
    {
        $nodes0 = $this->_request->get('nodes');
        $this->writeCmdLog(array('nodes'=>$nodes0));
        if(empty($nodes0)){
            return $this->setReturnError('at least one node needs given');
        }
        $nodes = explode(',',$nodes0);
        $nodeIpPort = array();
        $centerConfig = $this->getCenterConfig();
        foreach($nodes as $nd){
            if(isset($centerConfig->nodeLocation[$nd])){
                $r = $centerConfig->nodeLocation[$nd];
                $nodeIpPort[$r['ip'].':'.$r['port']] = $r;
            }else{
                return $this->setReturnError('node '.$nd.' not found');
            }
        }
        $oldServiceMap = $centerConfig->getServiceMap();
        foreach($centerConfig->serviceMapDeactive as $serviceName=>$byType){
            foreach($byType as $routeType=>$rs){
                foreach ($rs as $k=>$r){
                    $s = $r['ip'].':'.$r['port'];
                    if(isset($nodeIpPort[$s])){
                        $oldServiceMap[$serviceName][$routeType][]=$r;
                        unset($centerConfig->serviceMapDeactive[$serviceName][$routeType][$k]);
                    }
                }
            }
        }
        $centerConfig->setServiceMap($oldServiceMap);
        $this->updCenterConfig($centerConfig);
        $this->broadcastAction();
        $this->setReturnOK($nodes0.' reactived');
    }
    /**
     * 获取proxy当前状态
     */
    public function proxisStatusAction()
    {
        $centerConfig = $this->getCenterConfig();
        $ips = $this->_request->get('ips');
        $this->writeCmdLog(array('ips'=>$ips));
        try{
            if(empty($ips)){
                $r = array_keys($centerConfig->proxy);
            }else{
                $r = explode(',', $ips);
                foreach($r as $k=>$v){
                    $r[$k]= \Sooh\ServiceProxy\Config\XML2CenterConfig::getIp($v);
                }
            }
            $ret = $this->getProxiesStatus($r, 1);
            
            $this->_view->assign('proxiesStatus', $ret);
            $this->setReturnOK();
        }catch(\ErrorException $ex){
            $this->setReturnError("error:".$ex->getMessage());
        }
    }
    /**
     * 获取当前代理情况
     * 
     * 返回：
     * {
     *      current_tasks{
     *          proxy1_ip => {
     *                   node1_name => num
     *                   ... ...
     *          },
     *          ... ...
     *      }
     * }
     */
    public function currentAction()
    {
        $rpt = new \Sooh\ServiceProxy\Struct\ProxyReportedStatus();
        $nodename = $this->_request->get('nodename');
        $proxyip = $this->_request->get('proxyip');
        $this->writeCmdLog(array('proxyip'=>$proxyip,'nodename'=>$nodename));
        $centerConfig = $this->getCenterConfig();
        $nodeNameMap = array();
        foreach($centerConfig->nodeLocation as $nm=>$r){
            $nodeNameMap[$r['ip'].':'.$r['port']]=$nm;
        }
        if(empty($proxyip)){
            $proxyipList = array_keys($centerConfig->proxyActive);
        }else{
            $proxyipList = array($proxyip);
        }
        $ret = array();
        $rpts = $this->getProxiesStatus($proxyipList,true);
        foreach($rpts as $rpt){
            foreach ($rpt->CurrentRequesting as $nodeIpPort=>$num){
                if($num<=0){
                    continue;
                }
                if(!empty($nodename)){
                    if($nodename==$nodeNameMap[$nodeIpPort]){
                        $ret[$rpt->ProxyIP][ $nodename ]=$num;
                    }
                }else{
                    $ret[$rpt->ProxyIP][ $nodeNameMap[$nodeIpPort] ]=$num;//,'Detail'=>array()
                }
            }
        }
        $this->_view->assign('current_tasks', $ret);
        $this->setReturnOK();
    }
    
    /**
     * 获取包含指定service-node或service-name的代理列表
     * 
     * 参数二选一：
     *  nodename:  包含指定节点名的那些proxy
     *  service:   包含指定服务的那些proxy
     * 返回
     * 	{
     *      macthed_position{
     * 		proxy1_ip:{
     * 			node1name=>port,
     * 			node2name=>port,
     * 		}
     *      }
     * 	}
     */
    public function findAction()
    {
        $limitNodeNameLike = $this->_request->get('nodename');
        $limitServiceNameLike = $this->_request->get('service');
        $this->writeCmdLog(array('nodename'=>$limitNodeNameLike,'service'=>$limitServiceNameLike));
        
        $ret = array();
        $centerConfig =$this->getCenterConfig();
        if(!empty($limitNodeNameLike)){
            foreach($centerConfig->nodeLocation as $nodeName=>$r){
                if(false!== strpos($nodeName, $limitNodeNameLike)){
                    $ret[$r['ip']][$nodeName]=$r['port'];
                }
            }
        }elseif(!empty($limitServiceNameLike)){
            $nodeNameMap = array();
            foreach($centerConfig->nodeLocation as $nodename=>$r){
                $nodeNameMap[$r['ip'].':'.$r['port']]=$nodename;
            }
            $tmp = $centerConfig->getServiceMap();
            foreach($tmp as $serviceName=>$byType){
                if(false=== strpos($serviceName, $limitServiceNameLike)){
                    continue;
                }
                foreach($byType as $rs){//$routeType=>
                    foreach($rs as $r){
                        $ret[$r['ip']][$nodeNameMap["{$r['ip']}:{$r['port']}"]]=$r['port'];
                    }
                }
            }
        }

        $this->_view->assign('macthed_position', $ret);
        $this->setReturnOK();
    }    
}
