<?php
/**
 * Created by PhpStorm.
 * User: YJSoft
 * Date: 2015-03-14
 * Time: 오후 3:32
 */
class LoginxeclientProvidernaver
{
	var $id = 'naver';
	var $title = '네이버';
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

		$output = new stdClass();

		if($xmlDoc->data->result->resultcode->body != '00')
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
				$output->message = $xmlDoc->data->result->message->body;
				return $output;
			}
		}

		$output->error=0;
		$output->message = 'success';
		$output->email = $xmlDoc->data->response->email->body;
		$output->nick_name = $xmlDoc->data->response->nickname->body;
		$output->state = $state;
		$output->enc_id = $xmlDoc->data->response->enc_id->body;
		$output->type = $service;
		$output->profile = $xmlDoc->data->response->profile_image->body;
		$output->enc_id = $xmlDoc->data->response->enc_id->body;

		return $output;
	}
}