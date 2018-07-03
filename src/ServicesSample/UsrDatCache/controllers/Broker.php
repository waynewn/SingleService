<?php
/**
 * Description of Broker
 *
 * @author wangning
 */
class BrokerController extends \SingleService\ServiceController{
    /**
     * 设置一个或多个值
     */
    public function setsAction()
    {
        $redis = $this->getRedis();
        $uid = $this->_request->get('uid');
        $list = $this->_request->get('list');
        $val = $this->_request->get('val');
        if(!is_array($list)){
            $list = explode(',',$list);
        }
        if(!is_array($uid)){
            $uid = explode(',',$uid);
        }
        foreach($list as $id){
            foreach($uid as $u){
                $this->setIntoRedis($redis, $id, $u, $val);
            }
        }
        $redis->close();
    }
    /**
     * 可以一次获得同一个用户的多个值
     * （如果没获取到，根据设置去获取并更新redis）
     * @param type $request
     * @param type $response
     */
    public function getsAction()
    {
        $redis = $this->getRedis();
        $uid = $this->_request->get('uid');
        $list = $this->_request->get('list');
        if(!is_array($list)){
            $list = explode(',',$list);
        }
        $ret = array();
        foreach($list as $id){
            $tmp = $this->getFromRedis($redis, $id,$uid);
            if(empty($tmp)){
                $this->setIntoRedis($redis,$id,$uid,$tmp);
            }
            $ret[$id]=$tmp;
        }
        $redis->close();
        $this->_view->assign('extendInfo', $ret);
    }
    /**
     * 
     * @return \Redis
     */
    protected function getRedis()
    {
        $dbIdentifier = $this->_Config->getIni('UsrDatCache.dbIdentifier');
        $dbIni = $this->_Config->getIni('DB.'.$dbIdentifier);
        $o=new \Redis();
        $o->connect($dbIni['server'], $dbIni['port']);
        if(!empty($dbIni['pass'])){
            $o->auth($dbIni['pass']);
        }
        return $o;
    }

    
    /**
     * 
     * @param \Redis $db
     * @param string $key
     * @param string $uid
     * @return string
     */
    protected function getFromRedis($db,$key,$uid)
    {
        if(empty($uid)){
            $db->select(0);
        }else{
            $db->select(1);
        }
        $fullKey = $this->buildKey($key, $uid);
        $data= $db->get($fullKey);
        if(empty($data)){
            $data = $this->getFromOtherSite($key,$uid);
            if(!empty($data)){
                $this->setIntoRedis($db,$key,$uid,$data);
            }
        }
        return $data;
    }
    /**
     * 设置值
     * @param \Redis $db
     * @param string $key
     * @param string $uid
     * @param string $val
     */
    protected function setIntoRedis($db,$key,$uid,$val)
    {
        if(empty($uid)){
            $db->select(0);
        }else{
            $db->select(1);
        }        
        if(empty($val) || $val===''){
            $db->del($this->buildKey($key, $uid));
        }else{
            $expire = $this->_Config->getIni('UsrDatMap.'.$key.'.expire');
            $this->_log->app_error('missing expire of key(in UsrDatMap):'.$key);
            if(empty($expire)){
                $expire=60;
            }
            $db->set($this->buildKey($key, $uid), $val, $expire);
        }
    }  
    /**
     * todo
     * @param type $key
     * @param type $uid
     */
    protected function getFromOtherSite($key,$uid)
    {
        $ini = $this->_Config->getIni('UsrDatMap.'.$key);
        $url = str_replace(array('{f}','{u}'),array($key,$uid),$ini['url']);
        $post = str_replace(array('{f}','{u}'),array($key,$uid),$ini['post']);
        $curl=$this->getCurl();
        if(empty($post)){
            $ret0 = $curl->httpGet($url);
        }else{
            $ret0 = $curl->httpPost($url,$post);
        }
        if($curl->httpCodeLast!==200 || empty($ret0->body)){
            $this->_log->app_error('try  get config from ini-service failed httpCode='.$curl->httpCodeLast.' response='.$ret0->body);
            return null;
        }
        if(!is_array($ret0->body)){
            $ret = json_decode($ret0->body,true);
        }else{
            $ret = $ret0->body;
        }
        if(!is_array($ret)){
            $this->_log->app_error('try  get config from ini-service failed httpCode='.$curl->httpCodeLast.' response='.$ret0->body);
            return null;
        }
        $loc = explode('.', $ini['jsonLoc']);
        while(sizeof($loc)){
            $k = array_shift($loc);
            $ret = $ret[$k];
        }
        return $ret;
    }
    
    protected function buildKey($key,$uid)
    {
        return $this->_Config->getIni('UsrDatCache.prefix').':'.$key.':'.$uid;
    }

}
