<?php
namespace SingleService;

class IniPermanent extends \Sooh\IniClasses\Vars{
    protected $_locker;
    protected $_ram;
    public function __construct() {
        $this->_locker = new \swoole_lock(SWOOLE_MUTEX);
        $this->_ram = new \swoole_buffer();
        $this->_ram->write(0, serialize(array('__###__sjkadgf__#!'=>1)));
        
    }
    public function gets($k)
    {
        if(empty($this->_vars)){
            $this->loadFromRam();
        }
        return parent::gets($k);
    }
    protected function loadFromRam()
    {
        $tmp = $this->_ram->read();
        $this->_vars = unserialize($tmp);
    }
    public function sets($k,$v)
    {
        if(empty($this->_vars)){
            $this->loadFromRam();
        }
        $ret = parent::sets($k,$v);
        if($this->_locker->lockwait()==false){
            return false;
        }
        $this->_ram->write(0, serialize($this->_vars));
        $this->_locker->unlock();
        return $ret;
    }
}
