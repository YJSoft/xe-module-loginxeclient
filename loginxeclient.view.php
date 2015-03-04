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

		//TODO 다국어화
		$logindata = new stdClass();
		$logindata->naver = new stdClass();
		$logindata->naver->id = 'naver';
		$logindata->naver->title = '네이버';
		$logindata->naver->connected = false;
		$logindata->github = new stdClass();
		$logindata->github->id = 'github';
		$logindata->github->title = '깃허브';
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

	function dispLoginxeclientOAuthFinish()
	{
		$oMemberModel = getModel('member');
		$oMemberController = getController('member');
		$oLoginXEServerModel = getModel('loginxeclient');
		$module_config = $oLoginXEServerModel->getConfig();

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

		if($service=='naver')
		{
			//받아온 인증키로 바로 회원 정보를 얻어옴
			$ping_url = 'https://apis.naver.com/nidlogin/nid/getUserProfile.xml';
			$ping_header = array();
			$ping_header['Host'] = 'apis.naver.com';
			$ping_header['Pragma'] = 'no-cache';
			$ping_header['Accept'] = '*/*';
			$ping_header['Authorization'] = sprintf("Bearer %s", $token);

			$request_config = array();
			$request_config['ssl_verify_peer'] = false;

			$buff = FileHandler::getRemoteResource($ping_url, null, 10, 'GET', 'application/x-www-form-urlencoded', $ping_header, array(), array(), $request_config);

			//받아온 결과 파싱(XML)
			$xml = new XmlParser();
			$xmlDoc= $xml->parse($buff);

			if($xmlDoc->data->result->resultcode->body != '00')
			{
				//연결도 못했다
				if(!$buff)
				{
					return new Object(-1,'Socket connection error. Check your Server Environment.');
				}
				//연결은 했는데 영 좋지 않은 결과가 나와 버림
				//p.s.)로그인 XE 서버 모듈에서 키가 넘어온 뒤 30분 이상이 지나서 인증시도가 된다면 오류가 남
				else
				{
					return new Object(-1,$xmlDoc->data->result->message->body);
				}
			}

			//이후 처리는 2가지로 분기함
			if(Context::get('is_logged'))
			{
				//로그인되어 있으면 현재 로그인된 회원의 srl과 provider로 enc_id 검색
				//있으면 바로 리턴
				//없으면 연동처리만 해줌

				//srl과 type로 회원 조회
				$cond = new stdClass();
				$cond->srl=Context::get('logged_info')->member_srl;
				$cond->type='naver';
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
					$naver_member->enc_id = $xmlDoc->data->response->enc_id->body;
					$naver_member->type='naver';

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
				$cond->enc_id=$xmlDoc->data->response->enc_id->body;
				$cond->type=$service;
				$output = executeQuery('loginxeclient.getLoginxeclientbyEncID', $cond);
				$config = $oMemberModel->getMemberConfig();

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
					/*
					 * $func_arg
					 * child
					 *  - email $xmlDoc->data->response->email->body;
					 *  - nick_name $xmlDoc->data->response->nickname->body;
					 *  - state $state
					 *  - enc_id $xmlDoc->data->response->enc_id->body;
					 *  - type $service
					 *  - profile $xmlDoc->data->response->profile_image->body
					 */
					$funcarg = new stdClass();
					$funcarg->email = $xmlDoc->data->response->email->body;
					$funcarg->nick_name = $xmlDoc->data->response->nickname->body;
					$funcarg->state = $state;
					$funcarg->enc_id = $xmlDoc->data->response->enc_id->body;
					$funcarg->type = $service;
					$funcarg->profile = $xmlDoc->data->response->profile_image->body;

					$this->_doLoginXEJoin($funcarg);
				}
			}
		}
		elseif($service=='github')
		{
			//받아온 인증키로 바로 회원 정보를 얻어옴
			$ping_url = 'https://api.github.com/user';
			$ping_header = array();
			$ping_header['Host'] = 'api.github.com';
			$ping_header['Pragma'] = 'no-cache';
			$ping_header['Accept'] = 'application/json';
			$ping_header['Authorization'] = sprintf("token %s", $token);

			$request_config = array();
			$request_config['ssl_verify_peer'] = false;

			$buff = FileHandler::getRemoteResource($ping_url, null, 10, 'GET', 'application/x-www-form-urlencoded', $ping_header, array(), array(), $request_config);

			//받아온 결과 파싱(JSON)
			$xmlDoc=json_decode($buff);

			if(!isset($xmlDoc->login))
			{
				//연결도 못했다
				if(!$buff)
				{
					return new Object(-1,'Socket connection error. Check your Server Environment.');
				}
				//연결은 했는데 영 좋지 않은 결과가 나와 버림
				//p.s.)로그인 XE 서버 모듈에서 키가 넘어온 뒤 30분 이상이 지나서 인증시도가 된다면 오류가 남
				else
				{
					return new Object(-1,'Error');
				}
			}

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
					$naver_member->enc_id = md5($xmlDoc->id);
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
				$cond->enc_id=md5($xmlDoc->id);
				$cond->type=$service;
				$output = executeQuery('loginxeclient.getLoginxeclientbyEncID', $cond);
				$config = $oMemberModel->getMemberConfig();

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
					/*
					 * $func_arg
					 * child
					 *  - email = $xmlDoc->email;
					 *  - nick_name = $xmlDoc->login;
					 *  - state $state
					 *  - enc_id md5($xmlDoc->id);
					 *  - type $service
					 *  - profile $xmlDoc->avatar_url
					 */
					$funcarg = new stdClass();
					$funcarg->email = $xmlDoc->email;
					$funcarg->nick_name = $xmlDoc->login;
					$funcarg->state = $state;
					$funcarg->enc_id = md5($xmlDoc->id);
					$funcarg->type = $service;
					$funcarg->profile = $xmlDoc->avatar_url;

					$this->_doLoginXEJoin($funcarg);
				}
			}
		}
		elseif($service=='xe')
		{
			return new Object(-1,sprintf(Context::getLang('loginxecli_not_supported_provider'), Context::getLang('loginxe_' . $service . '_provider')));
		}
		else
		{
			return new Object(-1,sprintf(Context::getLang('loginxecli_not_supported_provider'), Context::getLang('loginxe_unknown_provider')));
		}

		Context::set('url',$this->redirect_Url);
	}

	function generate_state() {
		$mt = microtime();
		$rand = mt_rand();
		return md5($mt . $rand);
	}

	/*
	 * $func_arg
	 * child
	 *  - email
	 *  - nick_name
	 *  - state
	 *  - enc_id
	 *  - type
	 *  - profile
	 */
	function _doLoginXEJoin($func_arg)
	{
		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();
		$oMemberController = getController('member');
		$oLoginXEServerModel = getModel('loginxeclient');
		$module_config = $oLoginXEServerModel->getConfig();

		// call a trigger (before)
		$trigger_output = ModuleHandler::triggerCall ('member.procMemberInsert', 'before', $config);
		if(!$trigger_output->toBool ()) return $trigger_output;
		// Check if an administrator allows a membership or module config allows external join function
		if($module_config->loginxe_joinenable=='') $module_config->loginxe_joinenable='true';
		if($config->enable_join != 'Y' || $module_config->loginxe_joinenable != 'true')
		{
			return new Object(-1,'msg_signup_disabled');
		}

		if($oMemberModel->getMemberInfoByEmailAddress($func_arg->email))
		{
			return new Object(-1,'loginxecli_duplicate_email');
		}

		$args = new stdClass();
		list($args->email_id, $args->email_host) = explode('@', $func_arg->email);
		$args->allow_mailing="N";
		$args->allow_message="Y";
		//$xmlDoc->data->response->email->body;
		$args->email_address=$func_arg->email;
		//md5($state) . '@' . $args->email_host;
		$args->find_account_answer=md5($func_arg->state) . '@' . $args->email_host;
		$args->find_account_question="1";
		//$xmlDoc->data->response->nickname->body;
		$args->nick_name=$func_arg->nick_name;
		while($oMemberModel->getMemberSrlByNickName($args->nick_name)){
			$args->nick_name=$func_arg->nick_name . substr(md5($func_arg->state . rand(0,9999)),0,5);
		}
		$args->password=md5($func_arg->state) . "a1#";
		$args->user_id=substr($args->email_id,0,20);
		$useN=FALSE;
		//if id's first char is number, add n for first char.
		if(preg_match('/[0-9]/',substr($args->user_id,0,1)))
		{
			$useN=TRUE;
			$args->user_id = 'lxe' . substr($args->email_id,0,17);
		}
		while($oMemberModel->getMemberInfoByUserID($args->user_id)){
			if($useN) $args->user_id = 'lxe' . substr($args->email_id,0,7) . substr(md5($func_arg->state . rand(0,9999)),0,10);
			else $args->user_id=substr($args->email_id,0,10) . substr(md5($func_arg->state . rand(0,9999)),0,10);
		}
		$args->user_name=$func_arg->nick_name;

		// remove whitespace
		$checkInfos = array('user_id', 'nick_name', 'email_address');
		$replaceStr = array("\r\n", "\r", "\n", " ", "\t", "\xC2\xAD");
		foreach($checkInfos as $val)
		{
			if(isset($args->{$val}))
			{
				$args->{$val} = str_replace($replaceStr, '', $args->{$val});
			}
		}

		$output = $oMemberController->insertMember($args);
		if(!$output->toBool()) return $output;

		$site_module_info = Context::get('site_module_info');
		if($site_module_info->site_srl > 0)
		{
			$columnList = array('site_srl', 'group_srl');
			$default_group = $oMemberModel->getDefaultGroup($site_module_info->site_srl, $columnList);
			if($default_group->group_srl)
			{
				$oMemberModel->addMemberToGroup($args->member_srl, $default_group->group_srl, $site_module_info->site_srl);
			}

		}

		$LoginXEMember = new stdClass();
		$LoginXEMember->srl = $args->member_srl;
		$LoginXEMember->enc_id = $func_arg->enc_id;
		$LoginXEMember->type=$func_arg->type;

		$output = executeQuery('loginxeclient.insertLoginxeclientMember', $LoginXEMember);
		if(!$output->toBool())
		{
			return new Object(-1,$output->message);
		}

		$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$args->email_id));
		if(!is_dir('./files/cache/tmp')) FileHandler::makeDir('./files/cache/tmp');

		$ping_header = array();
		$ping_header['Pragma'] = 'no-cache';
		$ping_header['Accept'] = '*/*';

		$request_config = array();
		$request_config['ssl_verify_peer'] = false;

		FileHandler::getRemoteFile($func_arg->profile, $tmp_file,null, 10, 'GET', null,$ping_header,array(),array(),$request_config);

		if(file_exists($tmp_file))
		{
			$oMemberController->insertProfileImage($args->member_srl, $tmp_file);
		}

		if($config->identifier == 'email_address')
		{
			$oMemberController->doLogin($args->email_address);
		}
		else
		{
			$oMemberController->doLogin($args->user_id);
		}

		$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

		if($config->redirect_url) $this->redirect_Url = $config->redirect_url;
		else $this->redirect_Url = getNotEncodedUrl('', 'act', 'dispMemberModifyInfo');

		FileHandler::removeFile($tmp_file);
	}
}
