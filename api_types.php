<?php

define('TR_API_LIST', ['Imagga']);

abstract class API
{
    abstract function getInfo() : array ;

    abstract function getParams() : array ; 

    abstract function getRemainingRequest($conf) : int;

    abstract function generateTags($params) : array;

    function getFileName($imageId) {
        $query = '
SELECT path
  FROM '.IMAGES_TABLE.'
  WHERE id = '.((int)$imageId).'
;';
        $result = query2array($query);
        return $result[0]['path'];
    }
}

foreach (TR_API_LIST as $apiName) {
    include_once(TR_PATH.'api_classes/'.$apiName.'.php');
}