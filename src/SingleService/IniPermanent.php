<?php
namespace SingleService;

class IniPermanent extends \Sooh\IniClasses\Vars{
    protected $_locker;
    protected $_ram;

    public function __construct($config) {
        $this->_locker = new \swoole_lock(SWOOLE_MUTEX);
        
        $this->_ram = new \swoole_table(10);
        $this->_ram->column('str', \swoole_table::TYPE_STRING, 100);
        $this->_ram->create();

    }
    
    public function onNewRequest()
    {
    }
    
    public function gets($k)
    {
        if($this->_locker->lockwait(1) == false){
            throw new \ErrorException("get from shm failed(locked)");
        }

        $ret = $this->_ram->get($k,'str');
        $this->_locker->unlock();
        return $ret;
    }

    public function sets($k,$v)
    {
        if($this->_locker->lockwait(1) == false){
            throw new \ErrorException("save to shm failed(locked)");
        }

        $this->_ram->set($k,array('str'=> $v));
        $this->_locker->unlock();

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
