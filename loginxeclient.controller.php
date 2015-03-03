<?php
class loginxeclientController extends loginxeclient
{
	function init()
	{
	}

	function triggerDeleteLoginxeclientMember($args)
	{
		$cond = new stdClass();
		$cond->srl = $args->member_srl;
		$output = executeQuery('loginxeclient.deleteLoginxeclientMember', $cond);

		return;
	}

	function triggerLoginxeclientAddMemberMenu()
	{
		debugPrint('ADD MEMBER MENU');
		$logged_info = Context::get('logged_info');
		if(!Context::get('is_logged')) return new Object();
		//$target_srl = Context::get('target_srl');

		$oMemberController = getController('member');
		$oMemberController->addMemberMenu('dispLoginxeclientListProvider', '로그인XE 제공자 연결');

		if($logged_info->is_admin== 'Y')
		{
			//$url = getUrl('','act','dispNcenterliteUserConfig','member_srl',$target_srl);
			//$oMemberController->addMemberPopupMenu($url, '유저 알림 설정', '');
		}

		return new Object();
	}
}