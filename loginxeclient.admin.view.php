<?php
class loginxeclientAdminView extends loginxeclient
{
  function init()
  {
    $this->setTemplatePath($this->module_path . 'tpl');
    $this->setTemplateFile(strtolower(str_replace('dispLoginxeclientAdmin', '', $this->act)));
  }

  function dispLoginxeclientAdminConfig()
  {
    $oLoginXEServerModel = getModel('loginxeclient');
    $module_config = $oLoginXEServerModel->getConfig();

    Context::set('module_config', $module_config);
  }
}
