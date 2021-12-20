<?php
/*
Plugin Name: Tag Recognition
Version: 0.1
Description: Use an external API to automatically tag an image
Plugin URI: 
Author: Zacharie Guet
Has Settings: true
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

define('TR_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
define('TR_ID',      basename(dirname(__FILE__)));
define('TR_ADMIN',   get_root_url() . 'admin.php?page=plugin-' . TR_ID);

/*
 * Event handlers
 */
add_event_handler('init', 'tag_recognition_init');
add_event_handler('ws_add_methods', 'admin_ws_api_ws_add_methods');

include_once(TR_PATH . 'include/functions.php');
include_once(TR_PATH . 'include/ws_functions.php');
include_once(TR_PATH . 'conf.php');
include_once(TR_PATH . 'api_types.php');

function tag_recognition_init()
{
  global $conf;

  load_language('plugin.lang', TR_PATH);
}

// Usefull methods
function tr_getAPI($api) : API {

  if (!in_array($api, TR_API_LIST))
      return null;
  
  $ret = new Imagga();

  # if ($api = 'AWS') $ret = ...
  return $ret;
}

function tr_getConf() : TR_Conf {
  global $conf;

  return unserialize($conf['tag_recognition']);
}

function tr_setConf(TR_Conf $tr_conf) {
  conf_update_param('tag_recognition', serialize($tr_conf), true);
}


// Add a prefilter
add_event_handler('loc_end_picture_modify', 'tr_set_prefilter_modify');

function tr_set_prefilter_modify()
{
	global $template, $user;

  $template->assign('TR_PATH', TR_PATH);
  $template->assign('PWG_TOKEN', get_pwg_token());
  $template->assign('ACTUAL_API', tr_getConf()->getSelectedAPI());
  $template->assign('USER_LANG', explode('_', $user['language'])[0]);

  $template->set_filename('tr_picture_modify', realpath(TR_PATH.'template/picture_modify.tpl'));
  $template->assign_var_from_handle('TR_PICTURE_MODIFY', 'tr_picture_modify');
  $template->set_prefilter('picture_modify', 'tagRecognition_modify');
}

function tagRecognition_modify($content)
{
  $search = 'name="tags[]" multiple style="width:calc(100% + 2px);"></select>';

  $replacement = $search.'{$TR_PICTURE_MODIFY}';

  $ret = str_replace($search, $replacement, $content);

  return $ret;
}

?>
