<?php
namespace SingleService;

/**
 * Description of Request
 *
 * @author wangning
 */
class Request {
    protected $_req;
    public function __construct($req) {
        $this->_req = $req;
    }
    protected $_params=array();
    public function setParam($k,$v)
    {
        $this->_params[$k]=$v;
    }
    public function getParam($k)
    {
        return $this->_params[$k];
    }
    public function get($key)
    {
        if(isset($this->_req->get[$key])){
            return $this->_req->get[$key];
        }elseif(isset($this->_req->post[$key])){
            return $this->_req->post[$key];
        }else{
            
            
            return $this->_params[$key];
        }
    }
}
