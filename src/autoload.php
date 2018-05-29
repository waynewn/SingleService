<?php

function autoload_sooh2($class){
    $tmp = explode('\\', $class);
    if(sizeof($tmp)==1){
        return false;
    }
    $cmp = array_shift($tmp);
    if($cmp===''){
        $cmp = array_shift($tmp);
    }
    if($cmp=='Sooh2'){
        include '/root/Sooh2/'.implode('/', $tmp).'.php'; //Sooh2的路径
        return true;
    }else{
        return false;
    }
}
spl_autoload_register('autoload_sooh2');

function autoload_locallib($class){
    $tmp = explode('\\', $class);
    if(sizeof($tmp)==1){
        return false;
    }
    $cmp = array_shift($tmp);
    if($cmp===''){
        $cmp = array_shift($tmp);
    }

    switch($cmp){//                       公司类库的路径
        case 'Lib':$f = '/application/library/Lib/'.implode('/', $tmp).'.php';break;
        case 'Prj':$f = '/application/library/Prj/'.implode('/', $tmp).'.php';break; 
        case 'Rpt':$f = '/application/library/Rpt/'.implode('/', $tmp).'.php';break; 
    }
    //error_log(">>>>>autoload:>>>>>>>>>$cmp>>>>>>".$f);
    if(is_file($f)){
        include $f;
        return true;
    }else{
        return false;
    }
}
spl_autoload_register('autoload_locallib');