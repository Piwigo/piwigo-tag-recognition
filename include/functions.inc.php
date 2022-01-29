<?php

function tr_getAPI($api) : API {

    if (!in_array($api, TR_API_LIST))
        return null;

    $ret = new Imagga();

    if ($api == 'Azure') $ret = new Azure();
    
    return $ret;
}

function tr_getConf() : TR_Conf {
    global $conf;

    return unserialize($conf['tag_recognition']);
}

function tr_setConf(TR_Conf $tr_conf) {
    conf_update_param('tag_recognition', serialize($tr_conf), true);
}

function tr_createAndAssignTags($tags, $imageId) {
// If the tag is just one value, transform it to an array
  $tag_names = (is_array($tags))? $tags:[$tags];

  include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

  $tag_ids = array();
  $return_info = array();

  foreach ($tag_names as $tag_name) 
  {
    $escaped_tag_name =  pwg_db_real_escape_string($tag_name);

    $creation_output = create_tag($escaped_tag_name);
  
    if (isset($creation_output['error'])) // The tag already exist, we have to find the id
    {
      
      $query = '
SELECT id
  FROM '.TAGS_TABLE.'
  WHERE name = "'. $escaped_tag_name .'"
;';
      $existing_tags = query2array($query, null, 'id');
      if (count($existing_tags) > 0)
      {
        array_push($tag_ids, $existing_tags[0]);
        array_push($return_info, [
          'tag' => $escaped_tag_name,
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
  WHERE image_id = '.pwg_db_real_escape_string($imageId).'
;';
  
  $associated_tags = query2array($query, null, 'tag_id');
  foreach ($associated_tags as $tag_id) 
  {
    if (!in_array($tag_id, $tag_ids)) 
    {
      array_push($tag_ids, $tag_id);
    }
  }

  set_tags($tag_ids, $imageId);

  return $return_info;
}