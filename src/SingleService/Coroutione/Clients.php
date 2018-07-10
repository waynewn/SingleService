<?php
namespace SingleService\Coroutione;
include __DIR__.'/Client.php';
/**
 * Description of Clients
 *
 * @author wangning
 */
class Clients {
    /**
     * @return \SingleService\Coroutione\Clients
     */
    public static function create($timeout=5)
    {
        $o =  new Clients();
        $o->timeout=$timeout;
        return $o;
    }
    protected $timeout=5;
    /**
     *
     * @var \Sooh\ServiceProxy\Log\Txt
     */
    protected $log;
    protected $arr=array();
    public function addTask($ip,$port,$uriWithQueryString,$args4Post=null)
    {
        $this->arr["http://$ip:$port/$uriWithQueryString"] = new \SingleService\Coroutione\Client($ip,$port,$uriWithQueryString,$args4Post,$this->timeout);
    }

    /**
     * 获取所有请求的返回值(全部转化为数组)，
     *      httpcode 200的是字符串，其他的是数字（http code）
     * 
     * @param bool $isLastTry 是否跳过超时设置直接拿结果（当超时处理），
     * @param bool  $ipPortOnly 返回值的键值只保留IP:PORT部分（全部是往不同服务器发送时，可以不用url做主键）
     * @return array
     */
    public function getResultArrayAndFree($isLastTry=false,$ipPortOnly=false)
    {
        $secondsSleepPerStep=0.2;
        $stepMax = ceil($this->timeout/$secondsSleepPerStep);
        $ret = array();
        if($isLastTry==false){
            for($i=1;$i<$stepMax;$i++){//少一次用于最后强制结束的循环
                foreach($this->arr as $k=>$client){
                    $tmp = $client->tryGetResultAndFree();
                    if($tmp!==null){
                        unset($this->arr[$k]);
                        if($ipPortOnly){
                            $tmpr = explode('/', $k);
                            $k = $tmpr[2];
                        }
                        $ret[$k] = $tmp;
                    }
                }
                if(sizeof($this->arr)){
                    \co::sleep($secondsSleepPerStep);
                }
            }
        }
        foreach($this->arr as $k=>$client){
            $tmp = $client->tryGetResultAndFree(true);
            unset($this->arr[$k]);
            $ret[$k] = $tmp;
        }
        return $ret;
    }
    /**
     * 获取所有请求的返回值(第一级当obj，第二级开始还是数组)，
     *      httpcode 200的是字符串，其他的是数字（http code）
     * 
     * @param bool $isLastTry 是否跳过超时设置直接拿结果（当超时处理），默认否
     * @param bool  $ipPortOnly 返回值的键值只保留IP:PORT部分（全部是往不同服务器发送时，可以不用url做主键）
     * @return array
     */
    public function getResultObjAndFree($isLastTry=false,$ipPortOnly=false)
    {
        $ret = $this->getResultArrayAndFree($isLastTry,$ipPortOnly);
        $finalRet=array();
        foreach ($ret as $uri=>$v){
            
            if($v[0]=='{'){
                $tmp = json_decode($v,true);
                $o = new \stdClass();
                foreach($tmp as $k=>$r){
                    $o->$k = $r;
                }
                $finalRet[$uri] = $o;
            }else{
                $finalRet[$uri] = $v;
            }
        }
        return $finalRet;
        
    }
}
