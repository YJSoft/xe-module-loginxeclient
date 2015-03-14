<?php
/**
 * Created by PhpStorm.
 * User: YJSoft
 * Date: 2015-03-14
 * Time: 오후 3:32
 */
class LoginxeclientProvidergithub
{
	var $id = 'github';
	var $title = '깃허브';
	/*
	 * must return stdClass object containing
		$output->email
		$output->nick_name
		$output->state
		$output->enc_id
		$output->type
		$output->profile
		$output->enc_id
	 */
	function sendOAuthRequest($token,$state,$service)
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

		$output = new stdClass();

		if(!isset($xmlDoc->login))
		{
			//연결도 못했다
			if(!$buff)
			{
				$output->error = -1;
				$output->message = 'Socket connection error. Check your Server Environment.';
				return $output;
			}
			//연결은 했는데 영 좋지 않은 결과가 나와 버림
			//p.s.)로그인 XE 서버 모듈에서 키가 넘어온 뒤 30분 이상이 지나서 인증시도가 된다면 오류가 남
			else
			{
				$output->error = -1;
				$output->message = 'Unknown api error';
				return $output;
			}
		}

		if(!isset($xmlDoc->email))
		{
			$output->error = -1;
			$output->message = 'loginxecli_no_email_public';
			return $output;
		}

		$output->error=0;
		$output->message = 'success';
		$output->email = $xmlDoc->email;
		$output->nick_name = $xmlDoc->login;
		$output->state = $state;
		$output->enc_id = md5($xmlDoc->id);
		$output->type = $service;
		$output->profile = $xmlDoc->avatar_url;

		return $output;
	}
}