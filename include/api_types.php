<?php

define('TR_API_LIST', ['Imagga', 'Azure','MyKeyworder']);

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
SELECT
    id,
    path,
    representative_ext
  FROM '.IMAGES_TABLE.'
  WHERE id = '.((int)$imageId).'
;';
        $image_infos = pwg_db_fetch_assoc(pwg_query($query));
        $src_image = new SrcImage($image_infos);

        $derivative_url = DerivativeImage::url(IMG_MEDIUM, $src_image);

        // if the derivative_url starts with "i", it means the client needs to request i.php,
        // meaning the derivative is not in the cache
        if (preg_match('/^i/', $derivative_url))
        {
            // we force the generation of the derivative in cache
            set_make_full_url();
            $derivative_url = DerivativeImage::url(IMG_MEDIUM, $src_image);
            unset_make_full_url();

            fetchRemote($derivative_url, $dest);

            $src_image = new SrcImage($image_infos);
            $derivative_url = DerivativeImage::url(IMG_MEDIUM, $src_image);
        }

        return $derivative_url;
    }
}

// Include all the API
foreach (TR_API_LIST as $apiName) {
    include_once(TR_PATH.'api_classes/'.$apiName.'.php');
}
