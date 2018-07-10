<?php
namespace SingleService\GateWay\Process;
class HttpGetForward extends Base{

    /**
     * 
     * @param \SingleService\GateWay\QueData $data
     * @return \SingleService\Ret
     */
    public function handle($processIni,$data) {
        error_log(var_export($processIni,true));
        $original = $this->getArrayData($data);
        error_log(var_export($original,true));
        $find = array_keys($original);
        $replace= array_values($original);
        
        $arr = array();
        foreach($processIni['args'] as $k=>$v){
            $arr[$k] = str_replace($find, $replace, $v);
        }
        //$r['args']=$arr;
        $fullUrl = $processIni['url'].'?'.http_build_query($arr);
        error_log("------>>>>>>>>>>HttpGetForward:".$fullUrl);
    }
    
}
