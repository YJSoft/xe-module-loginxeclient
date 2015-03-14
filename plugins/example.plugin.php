<?php
class LoginxeclientProviderExample
{
	/*
	 * 다음 두 값은 설정 페이지와 연결 페이지에서 사용하는 값입니다.
	 */
	var $id = 'example';
	var $title = '예제';
	/*
	 * 이 함수는 auth key를 이용, 실제 서버에 요청을 보내는 함수입니다.
	 * 반드시 다음 변수를 포함하고 있는 stdClass 변수를 반환해야 합니다.
		$output->error : 오류 발생시 0 이외의 값을 넣어 message와 함께 반환하면 됩니다.
		$output->message : 오류 발생시 출력할 메시지입니다.
		$output->email : 이메일 주소입니다. 비워 둘 경우 가입 폼에서 해당 값을 입력받도록 할 수 있습니다.
		$output->nick_name : 가입할 회원의 닉네임입니다.
		$output->state : 함수 호출 인자의 $state값을 그대로 저장하시면 됩니다.
		$output->enc_id : enc_id입니다. 네이버와 같이 enc_id를 제공하는 서비스는 그대로 사용을,
	                      그렇지 않은 경우에는 md5(id)등으로 해시를 생성하여 저장하시면 됩니다.
		$output->type : $service 변수를 그대로 반환하시면 됩니다.
		$output->profile : 가입할 회원의 프로필 사진의 url을 저장하시면 됩니다.
	 */
	function sendOAuthRequest($token,$state,$service)
	{
		//이 플러그인은 개발 예제용으로, 실제 서비스에 사용하실수 없습니다!
		$output = new stdClass();
		$output->error = -1;
		$output->message = 'Cannot use Example plugin as Provider.';
		return $output;
	}
}