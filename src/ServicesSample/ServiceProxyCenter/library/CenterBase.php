<?php
namespace Sys;

/**
 * 中央控制
 * todo ip限制
 * @author wangning
 */
class CenterBase extends CenterAlert{

    protected $configFilePath;
    public function __construct($confFilePath) {
        parent::__construct();
        $this->configFilePath = $confFilePath;
        $this->config=new \SingleService\Config\CenterConfig();
        $this->log = null;
        $this->clients = new \SingleService\Coroutione\Clients();
    }

    public function onListenStart()
    {
        \Sooh\ServiceProxy\Util\Curl::factory()->httpGet('http://127.0.0.1:'.$this->config->centerPort.'/'.MICRO_SERVICE_MODULENAME.'/center/startTimer',null,null,1);
    }

    public function getMyIp()
    {
        return $this->config->centerIp;
    }
    public function getMyPort()
    {
        return $this->config->centerPort;
    }

    /**
     * 获取指定proxy status 结果
     * 返回 array(
     *      'http://123.234.234.2:234/ServiceProxy/proxy/status'=>array(
     *          'startup' => '2018-02-28 11:53:36',
     *          'proxyip'=>'1.2.3.4',
     *          'configVersion' => '1.0.1',
     *          'request_time' => '2018-02-28 11:54:07',
     *          'nodelist' =>  array (
     *              'payment01' => 'ping命令执行结果',
     *              'payment02' => NULL,
     *          ),
     *          'proxy' =>array(
     *              'counter'=>array(
     *                  '分钟戳'=> '目标节点IP:port'=>计数
     *              ),
     *              'error'=>array(
     *                  '问题节点IP:port'=>array('timestamp'=>最后报错时间戳，Num=>连续失败次数)
     *              ),
     *          )
     *      ),
     * )
     * @param array $ips
     * @param int $skipNodeStatus 1：skip，0：withStatus
     * @return array  
     */
    protected function getProxiesStatus($ips,$skipNodeStatus=0)
    {
        $clients = \SingleService\Coroutione\Clients::create(90);
        
        if(!is_array($ips)){
            throw new \ErrorException('ips of proxy for getProxiesStatus() should be array, given:'. var_export($ips,true));
        }
        foreach($ips as $ip){
            $tmp = \Sooh\ServiceProxy\Config\ProxyConfig::factory($this->config->proxy[$ip]);
            $clients->addTask($tmp->myIp, $tmp->myPort,'/'.MICRO_SERVICE_MODULENAME.'/proxy/status?skipNodeStatus='.$skipNodeStatus);
        }
        
        $ret = $clients->getResultsAndFree();
        foreach ($ret as $k=>$v){
            $ret[$k] = json_decode($v,true);
        }
        return $ret;
    }
    /**
     * 反串行化还原出 ProxyConfig
     * @param string $proxyStr
     * @return \Sooh\ServiceProxy\Config\ProxyConfig
     */
    protected function getProxyConfigObjFromStr($proxyStr)
    {
        $tmp = \Sooh\ServiceProxy\Config\ProxyConfig::factory($proxyStr);
        $tmp->setServiceMap($this->config->getServiceMap());
        $tmp->LogConfig = $this->config->LogConfig;
        $tmp->monitorConfig = $this->config->monitorConfig;
        $tmp->envIni = $this->config->envIni;
        $tmp->centerIp = $this->config->centerIp;
        $tmp->centerPort=$this->config->centerPort;
        foreach($this->config->nodeLocation as $nodename=>$ipport){
            $tmp->nodename[ $ipport['ip'].':'.$ipport['port'] ]=$nodename;
        }
        $tmp->setRewrite($this->config->getRewrite());
        return $tmp;
    }
}
