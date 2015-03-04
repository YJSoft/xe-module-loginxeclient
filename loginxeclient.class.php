<?php
class loginxeclient extends ModuleObject
{
  private $triggers = array(
      array('member.deleteMember', 'loginxeclient', 'controller', 'triggerDeleteLoginxeclientMember', 'after'),
	  array('moduleHandler.init', 'loginxeclient', 'controller', 'triggerLoginxeclientAddMemberMenu', 'after'),
	  array('member.procMemberModifyInfo', 'loginxeclient', 'controller', 'triggerDisablePWChk', 'after')
  );
//d
  function moduleInstall()
  {
    $oModuleController = getController('module');

    foreach($this->triggers as $trigger)
    {
      $oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
    }

    return new Object();
  }

  function checkUpdate()
  {
  	$oDB = &DB::getInstance();
    $oModuleModel = getModel('module');

    foreach($this->triggers as $trigger)
    {
      if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
      {
        return true;
      }
    }

  	if($oDB->isColumnExists("loginxeclient_member", "is_loginxeonly")) return true;

    return false;
  }

  function moduleUpdate()
  {
	  $oDB = &DB::getInstance();
    $oModuleModel = getModel('module');
    $oModuleController = getController('module');

    foreach($this->triggers as $trigger)
    {
      if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
      {
        $oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
      }
    }
	  if($oDB->isColumnExists("loginxeclient_member", "is_loginxeonly"))
	  {
		  $oDB->dropColumn("loginxeclient_member", "is_loginxeonly");
	  }

    return new Object(0, 'success_updated');
  }

  function moduleUninstall()
  {
    return new Object();
  }

  function recompileCache()
  {
    return new Object();
  }

  function checkOpenSSLSupport()
  {
    if(!in_array('ssl', stream_get_transports())) {
      return FALSE;
    }
    return TRUE;
  }
}
