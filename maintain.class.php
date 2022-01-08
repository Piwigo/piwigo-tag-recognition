<?php

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (!defined("TR_PATH"))
{
  define('TR_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
}

include_once(TR_PATH.'include/conf.php');

class tag_recognition_maintain extends PluginMaintain
{
  function install($plugin_version, &$errors=array())
  {
    global $conf;

    $default_conf = new TR_Conf();

    if (empty($conf['tag_recognition']))
    {
      conf_update_param('tag_recognition', $default_conf, true);
    }
    else
    {
      $old_conf = unserialize($conf['tag_recognition']);

      conf_update_param('tag_recognition', $old_conf, true);
    }
  }

  function uninstall()
  {
    conf_delete_param('tag_recognition');
  }

  function update($old_version, $new_version, &$errors=array())
  {
    $this->install($new_version, $errors);
  }
}
?>
