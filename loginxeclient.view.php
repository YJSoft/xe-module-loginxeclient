<?php
class loginxeclientView extends loginxeclient
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispLoginxeclient', '', $this->act)));
	}

	function dispLoginxeclientListProvider()
	{
		$oLoginXEServerModel = getModel('loginxeclient');
		$module_config = $oLoginXEServerModel->getConfig();

		Context::set('module_config', $module_config);

		$oMemberModel = getModel('member');
		$oMemberConfig = $oMemberModel->getMemberConfig();
		$skin = $oMemberConfig->skin;

		if(!$skin)
		{
			$skin = 'default';
			$template_path = sprintf('./modules/member/skins/%s', $skin);
		}
		else
		{
			//check theme
			$config_parse = explode('|@|', $skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/member/', $config_parse[0]);
			}
			else
			{
				$template_path = sprintf('./modules/member/skins/%s', $skin);
			}
		}

		Context::set('memberskin',$template_path);

		$logindata = new stdClass();
		$logindata->naver = new stdClass();
		$logindata->naver->id = 'naver';
		$logindata->naver->title = Context::getLang('loginxe_naver_provider');
		$logindata->naver->connected = false;
		$logindata->github = new stdClass();
		$logindata->github->id = 'github';
		$logindata->github->title = Context::getLang('loginxe_github_provider');
		$logindata->github->connected = false;

		$cond = new stdClass();
		$cond->srl=Context::get('logged_info')->member_srl;
		$cond->type='naver';
		$output = executeQuery('loginxeclient.getLoginxeclientMemberbySrl', $cond);

		if(isset($output->data->enc_id))
		{
			$logindata->naver->connected = true;
		}

		$cond = new stdClass();
		$cond->srl=Context::get('logged_info')->member_srl;
		$cond->type='github';
		$output = executeQuery('loginxeclient.getLoginxeclientMemberbySrl', $cond);

		if(isset($output->data->enc_id))
		{
			$logindata->github->connected = true;
		}

		Context::set('providers',$logindata);
	}

	function dispLoginxeclientOAuthFinish()
	{
		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();
		Context::set('member_config',$config);

		$skin = $config->skin;

		if(!$skin)
		{
			$skin = 'default';
			$template_path = sprintf('./modules/member/skins/%s', $skin);
		}
		else
		{
			//check theme
			$config_parse = explode('|@|', $skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/member/', $config_parse[0]);
			}
			else
			{
				$template_path = sprintf('./modules/member/skins/%s', $skin);
			}
		}

		Context::set('memberskin',$template_path);

		$oMemberController = getController('member');
		$oLoginXEServerModel = getModel('loginxeclient');
		$module_config = $oLoginXEServerModel->getConfig();

		//use_sessiondata가 true면 로그인 서버에 다시 요청하지 않음(key 만료로 인한 오류 방지)
		if(Context::get('use_sessiondata')=='true') return;
		if(Context::get('token')=='') return new Object(-1,'No token given.');

		$token = rawurldecode(Context::get('token'));
		if($token=='') return new Object(-1,'No token given.');
		$state = Context::get('state');
		$service = Context::get('provider');

		//SSL 미지원시 리턴
		if(!$this->checkOpenSSLSupport())
		{
			return new Object(-1,'loginxecli_need_openssl');
		}

		//state가 다르면 리턴(CSRF 방지)
		if($state!=$_SESSION['loginxecli_state'])
		{
			return new Object(-1,'msg_invalid_request');
		}

		//활성화된 서비스가 아닐경우 오류 출력
		if(!in_array($service, $module_config->loginxe_provider))
		{
			return new Object(-1,sprintf(Context::getLang('loginxecli_not_enabled_provider'), Context::getLang('loginxe_' . $service . '_provider')));
		}

		$plugin_path = sprintf('%splugins/%s.plugin.php', $this->module_path,str_replace('/','',$service));
		require($plugin_path);
		if(!class_exists('LoginxeclientProviderClass')) return new Object(-1,'msg_invalid_request');

		$oauth_value = LoginxeclientProviderClass::sendOAuthRequest($token,$state,$service);
		if($oauth_value->error!=0) return new Object($oauth_value->error,$oauth_value->message);

		//이후 처리는 2가지로 분기함
		if(Context::get('is_logged'))
		{
			//로그인되어 있으면 현재 로그인된 회원의 srl과 provider로 enc_id 검색
			//있으면 바로 리턴
			//없으면 연동처리만 해줌

			//srl과 type로 회원 조회
			$cond = new stdClass();
			$cond->srl=Context::get('logged_info')->member_srl;
			$cond->type=$service;
			$output = executeQuery('loginxeclient.getLoginxeclientMemberbySrl', $cond);

			if(isset($output->data->enc_id))
			{
				//리턴
				return new Object(-1,'loginxecli_already_registered');
			}
			else
			{
				//연동시킴
				$naver_member = new stdClass();
				$naver_member->srl = Context::get('logged_info')->member_srl;
				$naver_member->enc_id = $oauth_value->enc_id;
				$naver_member->type=$service;

				$output = executeQuery('loginxeclient.insertLoginxeclientMember', $naver_member);
				if(!$output->toBool())
				{
					return new Object(-1,$output->message);
				}
				//$this->redirect_Url = getUrl('');
				return new Object(-1,'loginxecli_linksuccess');
			}
		}
		else
		{
			//로그인이 안되어 있다면 enc_id로 가입 여부 체크
			$cond = new stdClass();
			$cond->enc_id=$oauth_value->enc_id;
			$cond->type=$service;
			$output = executeQuery('loginxeclient.getLoginxeclientbyEncID', $cond);

			//srl이 있다면(로그인 시도)
			if(isset($output->data->srl))
			{

				$member_Info = $oMemberModel->getMemberInfoByMemberSrl($output->data->srl);
				if($config->identifier == 'email_address')
				{
					$oMemberController->doLogin($member_Info->email_address,'',false);
				}
				else
				{
					$oMemberController->doLogin($member_Info->user_id,'',false);
				}

				//회원정보 변경시 비밀번호 입력 없이 변경 가능하도록 수정
				$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

				if($config->after_login_url) $this->redirect_Url = $config->after_login_url;
				$this->redirect_Url = getUrl('');
			}
			else
			{
				$_SESSION['loginxetemp_joindata'] = $oauth_value;
				return;
			}
		}

		if($service=='kakao')
		{
			return new Object(-1,sprintf(Context::getLang('loginxecli_not_supported_provider'), Context::getLang('loginxe_' . $service . '_provider')));
		}

		Context::set('url',$this->redirect_Url);
	}

	function dispLoginxeclientOAuth()
	{
		//oauth display & redirect act
		//load config here and redirect to service
		//key check & domain check needed
		//needed value=service,id,key,state(client-generated),callback-url(urlencoded)
		$service = Context::get('provider');
		$oLoginXEServerModel = getModel('loginxeclient');
		$module_config = $oLoginXEServerModel->getConfig();
		//설정에서 체크하지 않은 provider일 경우 잘못된 요청입니다 출력
		if(!in_array($service, $module_config->loginxe_provider))
		{
			return new Object(-1,'msg_invalid_request');
		}
		//state 생성
		$_SESSION['loginxecli_state'] = $this->generate_state();

		//서버 주소로 이동
		Context::set('url',$module_config->loginxe_server . sprintf("/index.php?module=loginxeserver&act=dispLoginxeserverOAuth&provider=%s&id=%s&key=%s&state=%s&callback=%s",$service,$module_config->loginxe_id,$module_config->loginxe_key,$_SESSION['loginxecli_state'],urlencode(getNotEncodedFullUrl('','act','dispLoginxeclientOAuthFinish','provider',$service))));
	}

	function dispLoginxeclientRevokeProvider()
	{
		if(!Context::get('is_logged'))
		{
			return new Object(-1,'msg_not_permitted');
		}
		$service = Context::get('provider');

		$cond = new stdClass();
		$cond->srl = Context::get('logged_info')->member_srl;
		$cond->type = $service;
		$output = executeQuery('loginxeclient.deleteLoginxeclientProvider', $cond);
		if(!$output->toBool())
		{
			return new Object(-1,$output->message);
		}

		return new Object(-1,'loginxecli_disconnected');

	}

	function generate_state() {
		$mt = microtime();
		$rand = mt_rand();
		return md5($mt . $rand);
	}
}
