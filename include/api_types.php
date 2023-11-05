<?php

define('TR_API_LIST', ['Imagga', 'Azure', 'StableDiff']);

abstract class API
{
    /**
     * Retrieve several infos about the API, has : 
     * - icon : a path to an image of the API
     * - site : the website of the company
     * - info : a short description of the API
     */
    abstract function getInfo() : array ;

    /**
     * Return an array key-value of the essential configuration of the api
     */
    abstract function getConfParams() : array ; 
    
    /**
     * Generate tags with the API
     * Need all the params from the method getConfParams in $conf to work
     * Need following keys in $params to work :
     *  - int : imageId
     *  - language code : language
     *  - int : limit 
     */
    abstract function generateTags($conf, $params) : array;

    /**
     * Usefull method that retrieve a filename with a image id
     */
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

// Include all the API
foreach (TR_API_LIST as $apiName) {
    include_once(TR_PATH.'api_classes/'.$apiName.'.php');
}