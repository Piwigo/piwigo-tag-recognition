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

include_once(TR_PATH . 'include/functions.inc.php');
include_once(TR_PATH . 'include/ws_functions.php');
include_once(TR_PATH . 'include/ws.php');
include_once(TR_PATH . 'include/conf.php');
include_once(TR_PATH . 'include/api_types.php');

$admin_file = TR_PATH . 'include/admin_events.inc.php';

function tag_recognition_init()
{
  global $conf;

  load_language('plugin.lang', TR_PATH);
}


// Add the picture modify actions
add_event_handler('loc_end_picture_modify', 'tr_loc_end_picture_modify',
EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_file);

add_event_handler('loc_end_element_set_global', 'tr_loc_end_element_set_global',
    EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_file);
add_event_handler('element_set_global_action', 'tr_element_set_global_action',
  EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_file);

?>
