<?php
namespace SingleService\GateWay\Process;
class Broker {
    /**
     * 
     * @return \SingleService\GateWay\Process\Broker
     */
    public static function factory ($queName,$config)
    {
        $nameSpace = $config->getMainModuleConfigItem('QueClassNamespace');
        $fullName = $nameSpace.$queName;
        $obj = new $fullName($queName,$config);
        return $obj;
    }
    protected $_processList;
    protected $_queName;    
    public function __construct($queName,$config) {
        $this->_queName = $queName;
        $conf = $config->getMainModuleConfigItem('GWPHttpForward');
        if(is_string($conf)){
            $conf = $config->getIni($conf);
        }
        if(empty($conf[$queName])){
            throw new \ErrorException("config for $queName not found");
        }
        $this->_processList = $conf[$queName];
    }
    /**
     * 处理并返回是否成功处理 （成功或失败）
     * @return \SingleService\Ret
     */
    public function handle($data){
        error_log(__FUNCTION__."::::::::::::::::::::;\n".var_export($data,true)."\n");

        foreach($this->_processList as $processIni){
            error_log(">>>>>>>.........................>>0 :". var_export($processIni,true));
            $processName = $processIni['method'];
            if(empty($processName)){
                throw new \ErrorException('method not defined for '.$this->_queName);
            }
            error_log(">>>>>>>..........................>>".$processName.":". var_export($processIni,true));
            $process = new $processName();
            $process->handle($processIni,$data);
        }
        return \SingleService\Ret::factoryOk();
    }
    
    
}