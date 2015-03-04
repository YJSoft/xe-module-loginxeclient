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

		$oModuleController->updateModuleConfig('loginxeclient', $config);


		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginxeclientAdminConfig'));
	}
}
