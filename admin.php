<?php
defined('TR_PATH') or die('Hacking attempt!');

global $template, $page, $conf;

include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');

$page['tab'] = (isset($_GET['tab'])) ? $_GET['tab'] : $page['tab'] = 'config';

$tabsheet = new tabsheet();
$tabsheet->add('config', '<span class="tr-icon-robot"></span>'.l10n('Configuration'), TR_ADMIN.'-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();

if (isset($_POST['use'])||isset($_POST['save']))
{
  check_pwg_token();
  if (in_array($_POST['api'], TR_API_LIST))
  {
    $newConf = tr_getConf();

    if (isset($_POST['use']))
    {
      $newConf->setSelectedAPI($_POST['api']);
    }
    
    $apiObject = tr_getAPI($_POST['api']);
    foreach ($apiObject->getConfParams() as $key => $value) {
      $newConf->setParam($_POST['api'], $key, $_POST[$key]);
    }
    tr_setConf($newConf);
  }
}

$tr_api_info = [];
$tr_api_params = [];
$tr_api_conf = [];

foreach (TR_API_LIST as $apiName) {
  $apiObject = tr_getAPI($apiName);
  $tr_api_info[$apiName] = $apiObject->getInfo();
  $tr_api_params[$apiName] = $apiObject->getConfParams();
  $tr_api_conf[$apiName] = tr_getConf()->getConf($apiName);
}



$template->assign(array(
  'TR_PATH' => TR_PATH,
  'TR_API_LIST' => TR_API_LIST,
  'TR_API_INFO' => $tr_api_info,
  'TR_API_PARAMS' => $tr_api_params,
  'TR_API_CONF' => $tr_api_conf,
  'TR_API_SELECTED' => tr_getConf()->getSelectedAPI(),
  'PWG_TOKEN' => get_pwg_token()
  ));

$template->set_filename('plugin_admin_content', realpath(TR_PATH . 'template/admin.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');

