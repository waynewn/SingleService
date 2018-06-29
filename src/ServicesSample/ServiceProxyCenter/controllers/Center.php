<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class CenterController extends \SingleService\ServiceController{
    /**
     * 
     * @return \Sooh\ServiceProxy\Config\CenterConfig
     */
    protected function getCenterConfig()
    {
        return $this->_Config->permanent->gets('centerConfig');
    }
    
    protected function writeCmdLog($args)
    {
        $this->log->syslog($this->_request->getActionName().':'. json_encode($args));
    }
    
    public function getProxyConfigAction()
    {
        $remoteAddr = $this->_request->getServerHeader('remote_addr');
        $this->writeCmdLog(array('proxyip'=>$remoteAddr));
        $proxyStr = $this->getCenterConfig()->proxy[$remoteAddr];
        if(empty($proxyStr)){
            $this->setHttpCode(404);
        }else{
            $tmp = $this->getProxyConfigObjFromStr($proxyStr);
            $arr = json_decode($tmp->toString(true),true);
            foreach($arr as $k=>$v){
                $this->_view->assign($k, $v);
            }
        }
    }
    public function reloadConfigAction()
    {
        try{
            $tmp = \Sooh\ServiceProxy\Config\XML2CenterConfig::parse($this->_Config->permanent->gets('locationOfXML'));
        }catch(\ErrorException $ex){
            $tmp = null;
        }
        if(!empty($tmp)){
            $this->config->copyFrom($tmp);
            $this->returnOK('new config version is: '.$this->config->configVersion);
        }else{
            $this->returnError(empty($ex)?"parse xml failed":$ex->getMessage());
        }
    }
}
