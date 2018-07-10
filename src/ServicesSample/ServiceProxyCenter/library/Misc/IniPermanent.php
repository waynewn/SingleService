<?php
namespace Sooh\ServiceProxy\Misc;

class IniPermanent extends \SingleService\IniPermanent{
    protected $_locker2;
    protected $_ramConfigLocation;
    protected $_ramConfig;
    protected $_centerConfig;
    public function __construct($config) {
        parent::__construct($config);
        $this->_locker2 = new \swoole_lock(SWOOLE_MUTEX);
        $len = $config->getMainModuleConfigItem("StrLenForConfig");
        if($len<=0){
            throw new \ErrorException('StrLenForConfig should be setted');
        }else{
            $len = $len *1024;
        }
        $this->_ramConfigLocation = new \swoole_table(2);
        $this->_ramConfigLocation->column('str', \swoole_table::TYPE_STRING, 256);
        $this->_ramConfigLocation->create();
        $this->_ramConfigLocation->set('loc',array('str'=>$config->getMainModuleConfigItem("LocOfServiceProxyXML")));
        $this->_ramConfigLocation->set('len',array('str'=>$len));
        
        $this->_ramConfig = new \swoole_table(1);
        $this->_ramConfig->column('str', \swoole_table::TYPE_STRING, $len);
        $this->_ramConfig->create();

    }
    
    public function getConfigLocation()
    {
        return $this->_ramConfigLocation->get('loc','str');
    }
    
    public function getConfigObj()
    {
        if($this->_centerConfig==null) {
            if($this->_locker2->lockwait(1) == false){
                throw new \ErrorException("get config from shm failed");
            }
            $tmp = $this->_ramConfig->get(0,'str');
            $this->_locker2->unlock();
            $this->_centerConfig = unserialize($tmp);
            if(empty($this->_centerConfig)){
                throw new \ErrorException("parse config from shm failed");
            }
        }
        return $this->_centerConfig;
    }
    
    public function updConfigObj($conf)
    {
        $buf = serialize($conf);
        $max = $this->_ramConfigLocation->get('len','str')-0;
        $real = strlen($buf);
        if($real>=$max){
            throw new \ErrorException("save config to shm failed(data too long: $real > $max)");
        }
        
        
        if($this->_locker2->lockwait(1) == false){
            throw new \ErrorException("save config to shm failed(locked)");
        }

        $this->_ramConfig->set(0,array('str'=> $buf));
        $this->_locker2->unlock();
        
        $this->_centerConfig = $conf;
    }
    
    public function onNewRequest()
    {
        $this->_centerConfig = null;
    }
    

    /**
     * 清空重置
     * @return Vars
     */
    public function free(){
        return $this;
    }
    
    public function reload(){
        
    }
}
