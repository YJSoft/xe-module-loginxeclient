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
		$oLoginXEClientModel = getModel('loginxeclient');
		$module_config = $oLoginXEClientModel->getConfig();
		$plugin_list = $oLoginXEClientModel->getPluginList();
		$plugin_data = $oLoginXEClientModel->getPluginData($plugin_list);

		Context::set('plugin_list', $plugin_list);
		Context::set('plugin_data', $plugin_data);
		Context::set('module_config', $module_config);
	}
}
