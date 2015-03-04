<?php
class loginxeclientAdminController extends loginxeclient
{
	function init()
	{
	}

	function procLoginxeclientAdminInsertConfig()
	{
		$oModuleController = getController('module');

		$config = Context::getRequestVars();

		if(substr($config->loginxe_server,-1)!='/')
		{
			$config->def_url .= '/';
		}
		if(!isset($config->loginxe_provider)) $config->loginxe_provider = array('NONE');

		$oModuleController->updateModuleConfig('loginxeclient', $config);

		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginxeclientAdminConfig'));
	}
}
