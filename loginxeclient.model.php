<?php
class loginxeclientModel extends loginxeclient
{
	function init()
	{
	}

	/**
	 * @brief 사용 가능한 플러그인 리스트 반환
	 */
	function getPluginList()
	{
		$plugins = array();
		$plugin_list = FileHandler::readDir(sprintf('%splugins', $this->module_path));
		foreach($plugin_list as $plugin_file)
		{
			if(!preg_match('/(.+)\.plugin.php/',$plugin_file)) continue;
			$plugin_tmp = str_replace('.plugin.php','',$plugin_file);
			if($plugin_tmp=='example') continue;
			$plugins[] = $plugin_tmp;
		}

		return $plugins;
	}

	function getPluginName($service)
	{
		$plugin_path = sprintf('%splugins/%s.plugin.php', $this->module_path, $service);
		require_once($plugin_path);
		if(!class_exists('LoginxeclientProvider' . $service)) return '';

		$class = 'LoginxeclientProvider' . $service;
		$instance = new $class();

		return $instance->title;
	}

	function getPluginData($list)
	{
		$logindata = new stdClass();
		foreach($list as $service)
		{
			$plugin_path = sprintf('%splugins/%s.plugin.php', $this->module_path, $service);
			require_once($plugin_path);
			if(!class_exists('LoginxeclientProvider' . $service)) continue;


			$class = 'LoginxeclientProvider' . $service;
			$instance = new $class();
			$logindata->{$service} = new stdClass();
			$logindata->{$service}->id = $instance->id;
			$logindata->{$service}->title = $instance->title;
			$logindata->{$service}->connected = FALSE;
		}

		return $logindata;
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
