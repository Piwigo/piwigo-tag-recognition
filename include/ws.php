<?php 

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

include_once(PHPWG_ROOT_PATH.'include/ws_core.inc.php');
include_once(PHPWG_ROOT_PATH.'include/common.inc.php');

function admin_ws_api_ws_add_methods($arr)
{
  $service = &$arr[0];

  $service->addMethod(
    'pwg.tagRecognition.getTags',
    'ws_tagRecognition_getTags',
    array(
      'api' =>  array(
        'default'=>null, 
        'info'=> 'If null, become the selected API',
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'language' =>  array(
        'default'=> 'en', 
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'imageId' =>  array(
        'default'=> null,
        'type'=> WS_TYPE_INT|WS_TYPE_POSITIVE
      ),
      'limit' => array('default'=> 20),
      'pwg_token' => array(),
    ),
    'Call the API and generate tags for the selected image'
  );

  $service->addMethod(
    'pwg.tagRecognition.createAndAssignTags',
    'ws_tagRecognition_createAndAssignTags',
    array(
      'imageId' =>  array( 
        'type'=> WS_TYPE_INT|WS_TYPE_POSITIVE,
      ),
      'tags' =>  array('flags'=>WS_PARAM_ACCEPT_ARRAY),
      'pwg_token' => array(),
    ),
    'Create tags in the database and assign them to the associated image'
  );

  $service->addMethod(
    'pwg.tagRecognition.generateAndAssignTags',
    'ws_tagRecognition_generateAndAssignTags',
    array(
      'api' =>  array(
        'default'=>null, 
        'info'=> 'If null, become the selected API',
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'language' =>  array(
        'default'=> 'en', 
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'imageId' =>  array(
        'default'=> null,
        'type'=> WS_TYPE_INT|WS_TYPE_POSITIVE
      ),
      'limit' => array('default'=> 20),
      'pwg_token' => array(),
    ),
    'Call the API, generate tags for the selected image and assign them to the image'
  );
}