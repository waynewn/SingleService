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
        \Sooh2\DB\KVObj::freeCopy(null);
        return true;
    }

    /**
     * 在执行action之后调用，做些额外工作，无返回值
     * @param bool $actionExecuted action 执行过还是没执行过
     */
    public function doAfterAction($actionExecuted)
    {
        \Sooh2\DB\KVObj::freeCopy(null);
    }
}
