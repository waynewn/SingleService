<?php
namespace GWLibs\Misc;

class Funcs{
    public static function trace($str)
    {
        error_log('mqtrace: '.$str);
    }
}

