<?php
namespace SingleService;
class ServiceController
{
    public function getRemoteAddr()
    {
        if(defined('SoohServiceProxyUsed')){
            $givedByProxy = $this->_Config->getini(SoohServiceProxyUsed.'.CookieNameForRemoteIP');
            $remoteAddr = $this->_request->getCookie($givedByProxy);
            if(!empty($remoteAddr)){
                return $remoteAddr;
            }
        }
        
        $xff = $this->_request->getServerHeader('x-forwarded-for');
        if(empty($xff)){
            return $this->_request->getServerHeader('remote_addr');
        }

        $tmp = explode(',', $xff);
        return $tmp[0];
        
    }
    
    protected function getRequestTime()
    {
        return $this->_request->getServerHeader('request_time');
    }
    public function initAllFromServer($request,$view,$config,$server,$log)
    {
        $this->_request=$request;
        $this->_view=$view;
        $this->_Config=$config;
        $this->_serverOfThisSingleService=$server;
        $this->_log=$log;
        if(!empty($this->successCode)){
            $this->_view->assign($this->successCode[0],$this->successCode[1]);
            $this->_view->assign($this->successCode[2],$this->successCode[3]);
        }
    }
    public function initPrjEnv($codeFieldName,$codeFieldSucc,$msgFieldName,$msgFieldSucc,$defaultErrCode)
    {
        //$this->successCode = array($codeFieldName,$codeFieldSucc,$msgFieldName,$msgFieldSucc,$defaultErrCode);
        
        if(!empty($this->_view)){
            $this->_view->assign($this->successCode[0],$this->successCode[1]);
            $this->_view->assign($this->successCode[2],$this->successCode[3]);
        }
    }
    /**
     * 
     * @param \SingleService\Ret $ret
     */
    protected function setReturn($ret)
    {
        if($ret->isOk()){
            $this->setReturnOK($ret->getMessage());
        }else{
            $this->setReturnError($ret->getMessage(),$ret->getErrorCode());
        }
    }    
    protected function setReturnOK($msg=null)
    {
        $this->_view->setResult(\SingleService\Ret::factoryOk($msg));
    }
    protected function setReturnError($msg,$code=null)
    {
        $this->_view->setResult(\SingleService\Ret::factoryError($msg,$code));
    }
    /**
     *
     * @var \SingleService\Loger 
     */
    protected $_log;
    /**
     *
     * @var \SingleService\Request 
     */
    protected $_request;

    
    /**
     *
     * @var \SingleService\View 
     */
    protected $_view;

    
    /**
     *
     * @var \Sooh\Ini 
     */
    protected $_Config;

    /**
     *
     * @var \SingleService\Server 
     */
    protected $_serverOfThisSingleService;
    
    /**
     * 在执行action之前调用，返回是否继续执行action
     * @return boolean 是否继续执行action
     */
    public function checkBeforeAction()
    {
        $this->getPlugin();
        if($this->_plugin){
            return $this->_plugin->checkBeforeAction();
        }else{
            return true;
        }
    }
    protected function getPlugin()
    {
        if(class_exists('\\Plugins\\Plugin',false)){
            $this->_plugin = call_user_func('\\Plugins\\Plugin::factory',$this->_request,$this->_view,$this->_Config,$this->_log);
        }
    }
    /**
     *
     * @var \SingleService\Plugin 
     */
    protected $_plugin;
    /**
     * 在执行action之后调用，做些额外工作，无返回值
     * @param bool $actionExecuted action 执行过还是没执行过
     */
    public function doAfterAction($actionExecuted)
    {
        if($this->_plugin){
            return $this->_plugin->doAfterAction($actionExecuted);
        }
    }

    /**
     * 设置 httpcode (重定向不需要这里设置301)
     * @param type $code
     */
    protected function setReturnHttpCode($code)
    {
        $this->_view->httpCodeAndNewLocation($code);
    }
    /**
     * 设置重定向
     * @param type $newLocation
     */
    protected function setReturnRedirect($newLocation)
    {
        $this->_view->httpCodeAndNewLocation(301,$newLocation);
    }
}

