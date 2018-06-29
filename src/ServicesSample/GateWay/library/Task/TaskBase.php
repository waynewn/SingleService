<?php
namespace GWLibs\Task;
/* 
 * 网关定时后台处理任务基类
 */

class TaskBase
{
    /**
     *
     * @var \Sooh\Ini 
     */
    protected $_Config;
    /**
     *
     * @var \SingleService\Loger 
     */
    protected $_log;
    public function __construct($config,$loger) {
        $this->_Config = $config;
        $this->_log = $loger;
    }

    public function init()
    {
        
    }
    
    public function free()
    {
        $this->_Config = null;
        $this->_log = null;
    }
    /** 
     * 指定类型消息处理
     * @param array $array 数据
     * @return \GWLibs\Ret
     */
    public function run($array)
    {
        return array(-1,'to do');
    }
}
