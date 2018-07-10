<?php
namespace SingleService\GateWay\Process;
class Base {
    protected function getArrayData($data)
    {
        if(is_array($data->queData)){
            return $data->queData; 
        }elseif(is_string($data->queData)){
            $tmp = json_decode($data->queData,true);
            if(!is_array($tmp)){
                throw new \ErrorException("queData is not json-type string($data->queData)");
            }else{
                return $tmp;
            }
        }elseif(is_object($data->queData)){
            return json_decode(json_encode($data->queData),true);
        }else{
            throw new \ErrorException("queData invalid :". var_export($data->queData,true));
        }
    }
    
    protected function getObjData($data)
    {
        if(is_array($data->queData)){
            return json_decode(json_encode($data->queData));
        }elseif(is_string($data->queData)){
            $tmp = json_decode($data->queData);
            if($tmp===false){
                throw new \ErrorException("queData is not json-type string($data->queData)");
            }else{
                return $tmp;
            }
        }elseif(is_object($data->queData)){
            return $data->queData;
        }else{
            throw new \ErrorException("queData invalid :". var_export($data->queData,true));
        }
    }
    
    protected function getStrData($data)
    {
        if(is_array($data->queData)){
            return json_encode($data->queData);
        }elseif(is_string($data->queData)){
            return $data->queData;
        }elseif(is_object($data->queData)){
            return json_encode($data->queData);
        }else{
            throw new \ErrorException("queData invalid :". var_export($data->queData,true));
        }
    }    
}
