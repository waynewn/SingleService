<?php
namespace Plugins;
/**
 * Description of Plugin
 *
 * @author wangning
 */
class Plugin extends \SingleService\Plugin{
    public function checkBeforeAction()
    {
        $this->_log->app_trace(__FUNCTION__.' for '.$this->_request->getModuleName().'/'.$this->_request->getControllerName().'/'.$this->_request->getActionName());
        return true;
    }

    /**
     * 在执行action之后调用，做些额外工作，无返回值
     * @param bool $actionExecuted action 执行过还是没执行过
     */
    public function doAfterAction($actionExecuted)
    {
        $this->_log->app_trace(__FUNCTION__.'('. var_export($actionExecuted,true).')  for '.$this->_request->getModuleName().'/'.$this->_request->getControllerName().'/'.$this->_request->getActionName());
    }
}
