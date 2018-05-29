<?php
namespace SingleService;

class Config
{
    /**
     * 获取或设置Ini 
     *   $newInstance_or_iniPath = null： 获取当前的实例
     *   $newInstance_or_iniPath = 配置文件路径： 初始化为使用sooh基本loger类
     *   $newInstance_or_iniPath = Ini实例： 初始化为使用自定义ini实例
     * @param mixed $newInstance_or_iniPath
     * @return \Sooh2\Misc\Ini
     */
    public static function getInstance($dirOrUrl,$name0)
    {
        if(empty($dirOrUrl)){
            throw new \ErrorException('missing args');
        }
        $name= ucfirst($name0);
        $obj = new Config();
        $obj->_ini_original=array('dirOrUrl'=>$dirOrUrl,'name'=>$name);
        $obj->reload();
        return $obj;

    }
    protected $_ini_original;
    public function reload()
    {
        $dirOrUrl = $this->_ini_original['dirOrUrl'];
        $name = $this->_ini_original['name'];
        if(strtolower(substr($dirOrUrl, 0,4))=='http'){
            $tmp = self::getConfigFromUrl($dirOrUrl,$name);
            $this->loaded[$name]=$tmp[$name];
            if(!empty($this->loaded[$name]['NeedsMoreIni'])){
                $tmp = self::getConfigFromUrl($dirOrUrl,$this->loaded[$name]['NeedsMoreIni']);
                foreach ($tmp as $k=>$v) {
                    $this->loaded[$k]=$v;
                }
            }
        }else{
            $this->loaded[$name]= $this->loadIniFile($dirOrUrl, $name);
            if(!empty($this->loaded[$name]['NeedsMoreIni'])){
                if($this->loaded[$name]['NeedsMoreIni']=='*'){//加载全部配置
                    $this->loadAllIni($dirOrUrl);
                }else{//加载指定额外配置
                    $ks = explode(',', $this->loaded[$name]['NeedsMoreIni']);
                    foreach($ks as $k){
                        if(is_dir($dirOrUrl.'/'.$k)){
                            $sub = scandir($dirOrUrl.'/'.$k);
                            foreach($sub as $k2){
                                $t = substr($k2, -4);
                                $name2 = substr($k2, 0,strpos($k2, '.'));
                                if($t=='.php'){
                                    $this->loaded[$k][$name2] = include $dirOrUrl.'/'.$k.'/'.$k2;
                                }elseif($t=='.ini'){
                                    $this->loaded[$k][$name2] = parse_ini_string(file_get_contents($dirOrUrl.'/'.$k.'/'.$k2),true);
                                }
                            }
                        }else{
                            $this->loaded[$k] = $this->loadIniFile($dirOrUrl, $k);
                        }
                    }
                }
            }
        }
        if(!isset($this->loaded[$name])){
            throw new \ErrorException('missing config for '.$name .' from '.$dirOrUrl);
        }
    }
    
    protected function loadAllIni($dirOrUrl)
    {
        $tmp  = scandir($dirOrUrl);
        foreach($tmp as $k){
            if($k[0]=='.'){
                continue;
            }
            $t = substr($k, -4);
            $name = substr($k, 0,strpos($k, '.'));
            if($t=='.php'){
                $this->loaded[$name] = include $dirOrUrl.'/'.$k;
            }elseif($t=='.ini'){
                $this->loaded[$name] = parse_ini_string(file_get_contents($dirOrUrl.'/'.$k),true);
            }elseif(is_dir($dirOrUrl.'/'.$k)){
                $sub = scandir($dirOrUrl.'/'.$k);
                foreach($sub as $k2){
                    $t = substr($k2, -4);
                    $name2 = substr($k2, 0,strpos($k2, '.'));
                    if($t=='.php'){
                        $this->loaded[$k][$name2] = include $dirOrUrl.'/'.$k.'/'.$k2;
                    }elseif($t=='.ini'){
                        $this->loaded[$k][$name2] = parse_ini_string(file_get_contents($dirOrUrl.'/'.$k.'/'.$k2),true);
                    }
                }
            }
        }
    }
    
    protected function loadIniFile($dir,$name)
    {
        if(is_file($dir.'/'.$name.'.ini.php')){
            return include $dir.'/'.$name.'.ini.php'; 
        }elseif(is_file($dir.'/'.$name.'.php')){
            return include $dir.'/'.$name.'.php'; 
        }elseif(is_file($dir.'/'.$name.'.ini')){
            return parse_ini_string(file_get_contents($dir.'/'.$name.'.ini'),true);
        }
    }

    protected static function getConfigFromUrl($url,$name)
    {
        $ret = \SingleService\Curl::factory()->httpGet($url.$name);
        if(!is_array($ret)){
            $ret = json_decode($ret,true);
            if(!is_array($ret)){
                throw new \ErrorException('missing config for '.$name .' from '.$url);
            }
        }
        if(empty($ret['ini_static'])){
            throw new \ErrorException('missing config for '.$name .' from '.$url);
        }
        return $ret['ini_static'];
    }
    public function dump()
    {
        return array(
            'default'=>$this->loaded,
            'runtime'=>$this->runtime,
            );
    }
    protected $basePath;
    protected $loaded = array();
    protected $runtime=array();
    /**
     * 设置运行时参数
     * @param string $k
     * @param mixed $v
     * @return Ini
     */
    public function setRuntime($k,$v)
    {
        $this->runtime[$k]=$v;
        return $this;
    }
    /**
     * 获取运行时参数
     * @param string $k
     * @return mixed
     */
    public function getRuntime($k)
    {
        if(isset($this->runtime[$k])){
            return $this->runtime[$k];
        }else{
            return null;
        }
    }
    /**
     * 获取serverid
     * @return int
     */
    public function getServerId()
    {
        if($this->runtime['serverId']){
            return $this->runtime['serverId']-0;
        }else{
            return 0;
        }
    }
    /**
     * 获取预定义参数
     * @param string $k
     * @throws \ErrorException
     * @return mixed
     */
    public function getIni($k)
    {
        $r = explode('.', $k);
        $f = array_shift($r);
        if(!isset($this->loaded[$f])){
            return null;
        }
        $tmp = $this->loaded[$f];
        foreach($r as $i){
            if(isset($tmp[$i])){
                $tmp = $tmp[$i];
            }else{
                return null;
            }
        }
        return $tmp;
    }
    /**
     * 设置Ini, 注意以下三点：
     * 1）如果存在相应的配置文件，请确认文件已被加载过再调用此函数
     * 2）key最大深度4层
     * 3）SingleService是工作在swoole环境下，这个设置除了当前任务进程，其他进程，可能有影响，可能没影响，尽量用getRuntime & setRuntime
     */
    public function setIni($k,$v)
    {
        $ks = explode('.', $k);
        switch(sizeof($ks)){
            case 1:
                $this->loaded[$ks[0]]=$v;
                break;
            case 2:
                $this->loaded[$ks[0]][$ks[1]]=$v;
                break;
            case 3:
                $this->loaded[$ks[0]][$ks[1]][$ks[2]]=$v;
                break;
            case 4:
                $this->loaded[$ks[0]][$ks[1]][$ks[2]][$ks[3]]=$v;
                break;
            default:
                throw new \ErrorException('max-depth=4 in ini->setIni');
        }
    }
    /**
     * 获取预定义文字串
     * @param string $k
     * @throws \ErrorException
     * @return mixed
     */
    public function getLang($k)
    {
        $r = explode('.', $k);
        $f = array_shift($r);
        $id = array_shift($r);
        if(sizeof($r)){
            throw new \ErrorException('one deep level support only in getLang');
        }
        if(!isset($this->loaded['LANG_'.$f])){
            $langPath = $this->getIni('application.langFullPath');
            $lang = $this->getIni('application.language');
            $file = $langPath.'/'.$f.'/'.$lang.'.txt';
            if(!is_file($file)){
                throw new \ErrorException('empty lang file found:'.$f.'.'.$lang);
            }else{
                $this->loaded["LANG_".$f]=parse_ini_file($file);
                
            }
        }
        if(isset($this->loaded["LANG_".$f][$id])){
            return $this->loaded["LANG_".$f][$id];
        }else{
            Loger::getInstance()->sys_warning('getLang('.$k.') failed');
            return $id;
        }
    }
}