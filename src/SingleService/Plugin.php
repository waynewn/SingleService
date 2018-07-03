<?php
namespace SingleService;

/**
 * Description of Plugin
 *
 * @author wangning
 */
class Plugin {
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
    public static function factory($request,$view,$config,$log)
    {
        $c = get_called_class();
        $obj = new $c;
        $obj->_request = $request;
        $obj->_view=$view;
        $obj->_Config=$config;
        $obj->_log = $log;
        return $obj;
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
     * 在执行action之前调用，返回是否继续执行action
     * @return boolean 是否继续执行action
     */
    public function checkBeforeAction()
    {
        return true;
    }

    /**
     * 在执行action之后调用，做些额外工作，无返回值
     * @param bool $actionExecuted action 执行过还是没执行过
     */
    public function doAfterAction($actionExecuted)
    {
        
    }
}
