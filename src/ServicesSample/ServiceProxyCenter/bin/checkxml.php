<?php
$libpath = dirname(__DIR__).'/library/';
include $libpath.'Config/CenterConfig.php';
include $libpath.'Config/LogConfig.php';
include $libpath.'Config/MonitorConfig.php';
include $libpath.'Config/ProxyConfig.php';
include $libpath.'Config/ServiceLocation.php';
include $libpath.'Config/XML2CenterConfig.php';

if (empty($argv[1])){
    die ("usage: php checkxml.php /root/path/to/xxxx.xml");
}

$xmlFile = $argv[1];
echo "start checking : $xmlFile...\n";
$ttttt=\Sooh\ServiceProxy\Config\XML2CenterConfig::parse($xmlFile);
$ttttt->envIni['MICRO_SERVICE_MODULENAME']='ABCDEFGHIGKLMNOPQRSTUVWXYZABCDEFGHIGKLMNOPQRSTUVWXYZ';
$s = serialize($ttttt);
echo "xml is ok, needs ini:StrLenForConfig >".ceil(strlen($s)/1000)."\n";