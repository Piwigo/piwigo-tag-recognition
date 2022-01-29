<?php 

function tr_loc_end_picture_modify()
{
  global $template, $user;

  $template->assign('TR_PATH', TR_PATH);
  $template->assign('PWG_TOKEN', get_pwg_token());
  $template->assign('ACTUAL_API', tr_getConf()->getSelectedAPI());
  $template->assign('USER_LANG', explode('_', $user['language'])[0]);

  $template->set_filename('tr_picture_modify', realpath(TR_PATH.'template/picture_modify.tpl'));
  $template->assign_var_from_handle('TR_PICTURE_MODIFY', 'tr_picture_modify');
  $template->set_prefilter('picture_modify', 'tr_picture_modify_page');
}

function tr_picture_modify_page($content)
{
  $search = 'name="tags[]" multiple style="width:calc(100% + 2px);"></select>';

  $replacement = $search.'{$TR_PICTURE_MODIFY}';

  $ret = str_replace($search, $replacement, $content);

  return $ret;
}

function tr_loc_end_element_set_global()
{
  global $template, $user;

  $template->assign('TR_PATH', TR_PATH);
  $template->assign('USER_LANG', explode('_', $user['language'])[0]);
  $template->set_filename('tr_batchmanager_action', realpath(TR_PATH.'template/batchmanager_action.tpl'));
  $content = $template->parse('tr_batchmanager_action', true);
  $template->append('element_set_global_plugins_actions', array(
    'ID' => 'tag_recognition',
    'NAME' => l10n('Tag Recognition'),
    'CONTENT' => $content,
    ));
}

function tr_element_set_global_action($action, $collection)
{
  global $page;

  if ($action == 'tag_recognition')
  {
    $page['infos'][] = l10n('Tag successfully generated and added');
  }
}