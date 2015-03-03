<?php
class loginxeclientModel extends loginxeclient
{
  function init()
  {
  }

  /**
   * @brief 모듈 설정 반환
   */
  function getConfig()
  {
    if(!$this->config)
    {
      $oModuleModel = getModel('module');
      $config = $oModuleModel->getModuleConfig('loginxeclient');
      unset($config->error_return_url);
      unset($config->module);
      unset($config->act);
      unset($config->xe_validator_id);

      $this->config = $config;
    }

    return $this->config;
  }
}
