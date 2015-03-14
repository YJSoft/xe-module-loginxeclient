<?php
/**
 * Created by PhpStorm.
 * User: YJSoft
 * Date: 2015-03-14
 * Time: 오후 3:32
 */
class LoginxeclientProviderkakao
{
	var $id = 'kakao';
	var $title = '카카오';
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
		//미완성!
		$output = new stdClass();
		$output->error = -1;
		$output->message = '아직 완성되지 않은 Provider입니다.';
		return $output;
	}
}