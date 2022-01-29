<?php

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

/**
 * API method
 * Call the API and generate tags for the selected image
 * @param mixed[] $params
 *    @option int imageId
 *    @option string api (optional)
 *    @option string language (optional)
 *    @option int limit (optional)
 */
function ws_tagRecognition_getTags($params, &$service) 
{

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $apiName = ($params['api'] != '')?  $params['api']:tr_getConf()->getSelectedAPI();
  $conf = tr_getConf()->getConf($apiName);

  $tags = tr_getAPI($apiName)->generateTags($conf, $params);
  try {
  } catch (\Throwable $th) {
    return new PwgError(403, 'API Error');
  }

  return $tags;
}


/**
 * API method
 * Create tags in the database and assign them to the associated image
 * @param mixed[] $params
 *    @option int imageId
 *    @option string[] tags
 */
function ws_tagRecognition_createAndAssignTags($params, &$service) 
{

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  return tr_createAndAssignTags($params['tags'], $params['imageId']);
}

/**
 * API method
 * Call the API, generate tags for the selected image and assign them to the image
 * @param mixed[] $params
 *    @option int imageId
 *    @option string api (optional)
 *    @option string language (optional)
 *    @option int limit (optional)
 */
function ws_tagRecognition_generateAndAssignTags($params, &$service) 
{

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $apiName = ($params['api'] != '')?  $params['api']:tr_getConf()->getSelectedAPI();
  $conf = tr_getConf()->getConf($apiName);

  try {
    $tags = tr_getAPI($apiName)->generateTags($conf, $params);
  } catch (\Throwable $th) {
    return new PwgError(403, 'API Error');
  }

  return tr_createAndAssignTags($tags, $params['imageId']);
}
