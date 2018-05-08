<?php
namespace SingleService;
class ServiceController
{
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
    public function initPrjEnv($codeFieldName,$codeFieldSucc,$msgFieldName,$msgFieldSucc)
    {
        $this->successCode = array($codeFieldName,$codeFieldSucc,$msgFieldName,$msgFieldSucc);
        if(!empty($this->_view)){
            $this->_view->assign($this->successCode[0],$this->successCode[1]);
            $this->_view->assign($this->successCode[2],$this->successCode[3]);
        }
    }
    
    protected function setReturnMsgAndCode($msg,$errCode=null)
    {
        $tmp = $this->_Config->dump();
        $newone = $this->_Config->getIni($msg);
        if($newone){
            $this->_view->assign($this->successCode[2],$newone);
        }else{
            $this->_view->assign($this->successCode[2],$msg);
        }
        if($errCode!==null){
            $this->_view->assign($this->successCode[0],$errCode);
        }else{
            $this->_view->assign($this->successCode[0],$this->successCode[1]);
        }
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
     * @var \SingleService\Config 
     */
    protected $_Config;

    /**
     * 
     * @param array $cookieOriginal
     * @return \SingleService\Curl
     */
    public function getCurl($cookieOriginal=array())
    {
        return \SingleService\Curl::factory($cookieOriginal);
    }
    /**
     *
     * @var \SingleService\Server 
     */
    protected $_serverOfThisSingleService;
    
    /**
     * 在执行action之前调用，返回是否继续执行action
     * @return boolean 是否继续执行action
     */
    protected function checkBeforeAction()
    {
        if(class_exists('\\Plugins\\Plugin',false)){
            $this->_plugin = call_user_func('\\Plugins\\Plugin::factory',$this->_request,$this->_view,$this->_Config,$this->_log);
            return $this->_plugin->checkBeforeAction();
        }else{
            return true;
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
    protected function doAfterAction($actionExecuted)
    {
        if($this->_plugin){
            $this->_plugin->doAfterAction($actionExecuted);
        }
    }

    /**
     * 设置 httpcode (重定向不需要这里设置301)
     * @param type $code
     */
    protected function setHttpCode($code)
    {
        $this->_serverOfThisSingleService->httpCodeAndNewLocation($code);
    }
    /**
     * 设置重定向
     * @param type $newLocation
     */
    protected function redirect($newLocation)
    {
        $this->_serverOfThisSingleService->httpCodeAndNewLocation(301,$newLocation);
    }
}

