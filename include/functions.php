<?php

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

/**
 * API method
 * Get the remaining requests this month for the selected API.
 * @param mixed[] $params
 *    @option string api (optional)
 */
function ws_tagRecognition_getRemainingRequest($params, &$service) {

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $apiName = $params['api'] ?? tr_getConf()->getSelectedAPI();
  $conf = tr_getConf()->getConf($apiName);

  return tr_getAPI($apiName)->getRemainingRequest($conf);
}

/**
 * API method
 * Get the remaining requests this month for the selected API.
 * @param mixed[] $params
 *    @option int imageId
 *    @option string api (optional)
 *    @option string language (optional)
 *    @option int limit (optional)
 */
function ws_tagRecognition_getTags($params, &$service) {

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $apiName = ($params['api'] != '')?  $params['api']:tr_getConf()->getSelectedAPI();
  $conf = array_merge(tr_getConf()->getConf($apiName), $params);

  return tr_getAPI($apiName)->generateTags($conf);
}


/**
 * API method
 * Create tags in the database and assign them to the associated image
 * @param mixed[] $params
 *    @option int imageId
 *    @option string[] tags
 */
function ws_tagRecognition_createAndAssignTags($params, &$service) {

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  // If the tag is just one value, transform it to an array
  $tag_names = (is_array($params['tags']))? $params['tags']:[$params['tags']];

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $tag_ids = array();
  $return_info = array();

  foreach ($tag_names as $tag_name) 
  {
    $creation_output = create_tag($tag_name);
  
    if (isset($creation_output['error'])) // The tag already exist, we have to find the id
    {
      $query = '
SELECT id
  FROM '.TAGS_TABLE.'
  WHERE name = \''.$tag_name.'\'
;';
      $existing_tags = query2array($query, null, 'id');
      if (count($existing_tags) > 0)
      {
        array_push($tag_ids, $existing_tags[0]);
        array_push($return_info, [
          'tag' => $tag_name,
          'id' => $existing_tags[0]
        ]);
      }
    } 
    else 
    {
      array_push($tag_ids, $creation_output['id']);
      array_push($return_info, [
        'tag' => $tag_name,
        'id' => $creation_output['id']
      ]);
    }
  }

  // Get the current associated tags
  $query = '
SELECT tag_id
  FROM '.IMAGE_TAG_TABLE.'
  WHERE image_id = '.pwg_db_real_escape_string($params['imageId']).'
;';
  
  $associated_tags = query2array($query, null, 'tag_id');
  foreach ($associated_tags as $tag_id) 
  {
    if (!in_array($tag_id, $tag_ids)) 
    {
      array_push($tag_ids, $tag_id);
    }
  }

  set_tags($tag_ids, $params['imageId']);

  return $return_info;
}