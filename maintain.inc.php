<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');


if (!defined("TR_PATH"))
{
  define('TR_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
}

function plugin_install()
{
  tr_set_conf();
}

function plugin_uninstall()
{
  tr_unset_conf();
}

function plugin_activate()
{
  tr_set_conf();
}

function tr_set_conf() {

  global $conf;

  $default_conf = new TR_Conf();

  if (empty($conf['tag_recognition']))
  {
    conf_update_param('tag_recognition', $default_conf, true);
  }
  else
  {
    $old_conf = tr_getConf();

    conf_update_param('tag_recognition', $old_conf, true);
  }
}

function tr_unset_conf() {
  conf_delete_param('tag_recognition');
}
?>
