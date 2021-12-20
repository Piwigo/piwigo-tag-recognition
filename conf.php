<?php

class TR_Conf 
{
    
    var $selected = "Imagga";

    var $conf = [];

    function getSelectedAPI() 
    {
        return $this->selected;
    }

    function setSelectedAPI($api) 
    {
        if (in_array($api,TR_API_LIST))
            $this->selected = $api;
    }

    function getConf($api) 
    {
        if (in_array($api,TR_API_LIST) && array_key_exists($api, $this->conf))
            return $this->conf[$api];
        return [];
    }

    function getParam($api, $param) 
    {
        if (in_array($api,TR_API_LIST) && array_key_exists($api, $this->conf))
            return $this->conf[$api][$param];
        return [];
    }

    function setParam($api, $param, $value) 
    {
        
        if (in_array($api,TR_API_LIST)) 
        {
            if(!array_key_exists($api, $this->conf))
                $this->conf[$api] = [];
            $this->conf[$api][$param] = $value;
        }
    }

}