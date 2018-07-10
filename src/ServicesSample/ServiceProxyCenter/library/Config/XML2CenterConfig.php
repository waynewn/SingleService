<?php
namespace Sooh\ServiceProxy\Config;
/**
 * xml配置文件转换成CenterConfig的工具类
 *
 * @author wangning
 */
class XML2CenterConfig {
    /**
     * 反串行化还原出 ProxyConfig
     * 
     * @return \Sooh\ServiceProxy\Config\ProxyConfig
     */
    public static function getProxyConfigObjFromStr($str,$centerConfig)
    {
        $tmp = \Sooh\ServiceProxy\Config\ProxyConfig::factory($str);
        $tmp->setServiceMap($centerConfig->getServiceMap());
        $tmp->LogConfig = $centerConfig->LogConfig;
        $tmp->monitorConfig = $centerConfig->monitorConfig;
        $tmp->envIni = $centerConfig->envIni;
        $tmp->centerIp = $centerConfig->centerIp;
        $tmp->centerPort=$centerConfig->centerPort;
        foreach($centerConfig->nodeLocation as $nodename=>$ipport){
            $tmp->nodename[ $ipport['ip'].':'.$ipport['port'] ]=$nodename;
        }
        $tmp->setRewrite($centerConfig->getRewrite());
        return $tmp;
    }
    
    public static function getIp($var)
    {
        $parts = explode('.',$var);
        if(sizeof($parts)!=4){
            throw new \ErrorException('invalid ip given:'.$var);
        }
        $ret= '';
        foreach($parts as $i){
            $i =$i-0;
            if($i>255 || $i<0 || !is_int($i)){
                throw new \ErrorException('invalid ip given:'.$var);
            }
            $ret .= '.'.$i;
        }
        return substr($ret,1);
    }
    /**
     * 解析配置文件路径，返回中央配置
     * @param string $file
     * @return \Sooh\ServiceProxy\Config\CenterConfig;
     */
    public static function parse($file)
    {
        if (!is_file($file)){
            throw new \ErrorException('xml file not found:'.$file);
        }
        $xml = simplexml_load_file($file);
        if (empty($xml)){
            throw new \ErrorException('not xml file');
        }
        $centerConfig = new \Sooh\ServiceProxy\Config\CenterConfig();
        foreach( $xml->runtime_ini->children() as $rini){
            $v = (string)$rini->attributes()->value;
            $k = (string)$rini->attributes()->name;
            if(!defined($k)){
                define($k, $v);
            }
            $centerConfig->envIni[$k]=$v;
        }

        $logConfig = new \Sooh\ServiceProxy\Config\LogConfig();
        $logConfig->root = rtrim((string)$xml->log->attributes()->dir,'/');

        $monitorConfig = new \Sooh\ServiceProxy\Config\MonitorConfig();
        foreach( $xml->monitor->services->children() as $service){
            $monitorConfig->services[(string)$service->attributes()->type] = (string)$service->attributes()->uri;
        }
        foreach( $xml->monitor->usergroup->children() as $ugrp){
            $monitorConfig->usersgroup[(string)$ugrp->attributes()->type] = (string)$ugrp->attributes()->data;
        }
        $monitorConfig->service = (string)$xml->monitor->attributes()->service;
        
        $centerConfig->LogConfig = $logConfig;
        $centerConfig->monitorConfig = $monitorConfig;
        $rootAttrs = $xml->attributes();
        $centerConfig->centerIp = self::getIp((string)$rootAttrs->centerIp);
        $centerConfig->centerPort = (int)$rootAttrs->centerPort;
        $centerConfig->configVersion = (string)$rootAttrs->version;
        if(empty($centerConfig->centerIp) || empty($centerConfig->centerPort) || empty($centerConfig->configVersion)){
            throw new \ErrorException('centerIp,centerPort,configVersion not setted');
        }
        self::fillRewrite($xml,$centerConfig);
        $tplFile = (string)$rootAttrs->node_templates;
        if(!empty($tplFile)){
            $templetes = self::getTemplatesFromFile($tplFile);
        }else{
            $templetes = self::getTemplates($xml);
        }

        $centerConfig->serviceMap[-1]=array();
        $centerConfig->serviceMapDeactive=array();
        if(empty($xml->servers)){
            throw new \ErrorException('xmlnode:servers missing');
        }
        if(count($xml->servers->children())==0){
            throw new \ErrorException('xmlnode:server in servers is missing');
        }
        //根据定义，确认所有的实例的配置以及对应的路由
        $centerConfig->proxyActive=array();
        foreach( $xml->servers->children() as $serv){
            $ip = self::getIp((string)$serv->attributes()->ip);
            if(isset($centerConfig->proxyActive[$ip])){
                throw new \ErrorException('xmlnode:server\'s ip duplicate');
            }else{
                $centerConfig->proxyActive[$ip] = (int)$serv->attributes()->proxyport;
                self::fillOneProxyConfig($serv,$centerConfig,$templetes);
            }
        }
        $centerConfig->setServiceMap($centerConfig->serviceMap[-1]);
        unset($centerConfig->serviceMap[-1]);

        return $centerConfig;
    }
    /**
     * 处理一个server
     * @return type
     */
    protected static function fillOneProxyConfig($serv,$centerConfig,$templetes)
    {
        $tmpConf = new \Sooh\ServiceProxy\Config\ProxyConfig();
        $tmpConf->myIp = self::getIp((string)$serv->attributes()->ip);
        $tmpConf->myPort = (int)$serv->attributes()->proxyport;
        //$tmpConf->rewriteRule = $centerConfig->rewrites;
        $tmpConf->configVersion=$centerConfig->configVersion;
        //一个server里可以没有service node
        foreach($serv->nodes->children() as $nd){
            $attr = $nd->attributes();
            $tpl = (string)$attr->templateId;
            $name = (string)$attr->name;
            $routeFlg = (string)$attr->route;
            if(empty($routeFlg)){
                $routeFlg = 'default';
            }
            $newPort = (int)$attr->port;
            $newRoot = rtrim((string)$attr->dir,'/');
            $active = (string)$attr->active;
            if(empty($active)){
                $active = 'yes';
            }
            if(!isset($templetes[$tpl])){
                throw new \ErrorException('template missing for id:'.$tpl);
            }
            $defined = $templetes[$tpl];
            if($newPort==0){
                $newPort=$defined['defaultPort'];
            }
            if(empty($newRoot)){
                $newRoot=$defined['defaultRoot'];
            }

            //端口，路径，状态基本检查
            if($newPort<=0 || !in_array($active, array('yes','no')) || empty($newRoot)){
                throw new \ErrorException('ini error with '.$tpl.'@'.$tmpConf->myIp.': port?status?dir');
            }
            
            //节点名字没定义或重复的情况，尝试转成唯一的
            if(empty($name)){
                throw new \ErrorException('ini error with '.$tpl.'@'.$tmpConf->myIp.': nodename not set');
            }
            if(isset($centerConfig->nodeLocation[$name])){
                throw new \ErrorException('ini error with nodename:'.$name.' duplicate');
            }
            
            //todo: 激活的service记录在-1里，剩下的放在-2里
            if($active=='yes'){
                foreach ($defined['services'] as $serviceModule){
                    $centerConfig->serviceMap[-1][$serviceModule][$routeFlg][]=array('ip'=>$tmpConf->myIp,'port'=>$newPort);
                    $centerConfig->serviceInNode[$serviceModule][]=$name;
                }
            }else{
                foreach ($defined['services'] as $serviceModule){
                    $centerConfig->serviceMapDeactive[$serviceModule][$routeFlg][]=array('ip'=>$tmpConf->myIp,'port'=>$newPort);
                    $centerConfig->serviceInNode[$serviceModule][]=$name;
                }
            }

            $tmpConf->nodeList[$name]=array(
                'start'=> str_replace('{dir}', $newRoot, $defined['cmd_start']),
                'stop'=>str_replace('{dir}', $newRoot, $defined['cmd_stop']),
                'ping'=>str_replace('{dir}', $newRoot, $defined['cmd_ping']),
                'reload'=>str_replace('{dir}', $newRoot, $defined['cmd_reload']),
                'patch'=>str_replace('{dir}', $newRoot, $defined['cmd_patch']),
                'active'=>$active,
                '_port_'=>$newPort,
            );
            $centerConfig->nodeLocation[$name]=array('ip'=>$tmpConf->myIp,'port'=>$newPort);
        }
        $centerConfig->proxy[$tmpConf->myIp]=$tmpConf->toString();
    }
    /**
     * @todo 没考虑在线热更新，减少rewrite的情况
     * @param type $xml
     * @param type $centerConfig
     */
    protected static function fillRewrite($xml,$centerConfig)
    {
        if(empty($xml->rewrite)){
            return ;
        }
        $newRewrite = array();
        foreach ($xml->rewrite->children() as $rewrite){
            $from = (string)$rewrite->attributes()->from;
            $to = (string)$rewrite->attributes()->to;
            if(empty($from) && !empty($to)){
                throw new \ErrorException('find error in rewrite:'.$from.'->'.$to);
            }
            if(!empty($from) && empty($to)){
                throw new \ErrorException('find error in rewrite:'.$from.'->'.$to);
            }
            $newRewrite[$from]=$to;
        }

        $centerConfig->setRewrite($newRewrite);
    }
    protected static function getTemplatesFromFile($file)
    {
        if (!is_file($file)){
            throw new \ErrorException('xml file not found:'.$file);
        }
        $xml = simplexml_load_file($file);
        if (empty($xml)){
            throw new \ErrorException('not xml file');
        }
        return self::getTemplates($xml);
    }
    protected static function getTemplates($xml)
    {
        $templetes = array();
        if(empty($xml->node_templates)){
            throw new \ErrorException('missing node-templetes');
        }
        foreach ($xml->node_templates->children() as $tpl){
            $id = trim((string)$tpl->attributes()->id);
            if(empty($id)){
                throw new \ErrorException('templete id missing');
            }
            $templetes[$id]=array(
                'defaultRoot' => rtrim((string)$tpl->attributes()->dir,'/'),
                'defaultPort' => (int)$tpl->attributes()->port,
                'cmd_start' => (string)$tpl->cmds->start->attributes()->cmd,
                'cmd_stop' => (string)$tpl->cmds->stop->attributes()->cmd,
                'cmd_ping' => (string)$tpl->cmds->ping->attributes()->cmd,
                'cmd_reload' => (string)$tpl->cmds->reload->attributes()->cmd,
                'cmd_patch' => (string)$tpl->cmds->patch->attributes()->cmd,
            );
            if(empty($templetes[$id]['defaultRoot']) || empty($templetes[$id]['defaultPort'])){
                throw new \ErrorException('templete '.$id.' missing root-dir or port');
            }
            $tmp = array();
            if(empty($tpl->serivices)){
                throw new \ErrorException('templete '.$id.' missing services');
            }
            foreach( $tpl->serivices->children() as $s){
                $tmp[] = strtolower((string)$s->attributes()->name);
            }
            if(empty($tmp)){
                throw new \ErrorException('templete '.$id.' missing services');
            }
            $templetes[$id]['services']=$tmp;
        }
        return $templetes;
    }
}
