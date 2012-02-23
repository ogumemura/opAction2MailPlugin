<?php

class opAction2MailPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect(
      'op_action.post_execute_message_sendToFriend',
      array($this,'sendToFriend')
    );
    $this->dispatcher->connect(
      'op_action.post_execute_friend_link',
      array($this,'sendToFriendLink')
    );
  }
  public function sendToFriendLink($event){

    if(version_compare(OPENPNE_VERSION, '3.5.0', '<=')){
      require_once(dirname(__FILE__).'/dummyload.php');
    }
  $action = $event['actionInstance'];
    if(sfRequest::POST != $action->getRequest()->getMethod()){
      return;
    }
    $id = $action->getUser()->getMemberId();
	$data = $action->getRequestParameter('friend_link');
    $member_from = Doctrine::getTable('Member')->find($id);
    $member_to = Doctrine::getTable('Member')->find($action->getRequestParameter('id'));
    $url = sfConfig::get('op_base_url');
    $message = <<<EOF
{$member_from['name']}さんからフレンド申請が届いています。

{$member_from['name']}さんからフレンド申請が届いています。
メッセージ：
{$data['message']}

フレンド申請を承認／拒否するには、こちらをクリックしてください。
{$url}/confirmation?category=friend_confirm
EOF;
     self::notifyMail($member_to,$message);
  }

  public function sendToFriend($event){
    if(version_compare(OPENPNE_VERSION, '3.5.0', '<=')){
      require_once(dirname(__FILE__).'/dummyload.php');
    }

    $action = $event['actionInstance'];

    if(sfRequest::POST != $action->getRequest()->getMethod()){
      return;
    }
    $id = $action->getUser()->getMemberId();
    $data = $action->getRequestParameter('message');
    $member_from = Doctrine::getTable('Member')->find($id);
    $member_to = Doctrine::getTable('Member')->find($data['send_member_id']);

    $url = sfConfig::get('op_base_url');    
    $message = <<<EOF
{$member_from['name']}さんからメッセージが届いています。
件名:{$data['subject']}

{$data['body']}

メッセージに返信するには、こちらをクリックしてください。
{$url}message
EOF;
    if(!$action->getRequestParameter('is_draft')){
      self::notifyMail($member_to,$message);
    }
  }

  public static function notifyMail($member,$message)
  {
    $memberPcAddress = $member->getConfig('pc_address');
    $memberMobileAddress = $member->getConfig('mobile_address');
    $from = opConfig::get('ZUNIV_US_NOTIFYFROM',opConfig::get('admin_mail_address'));

    list($subject,$body) = explode("\n",$message,2);
    if (0 != $member->getConfig('ZUNIV_US_NOTIFYPC',1) && $memberPcAddress)
    {
      $signature = opMailSend::getMailTemplate('signature', 'pc', array(), true, sfContext::getInstance());
      if ($signature) $signature = "\n".$signature;
      $body .= "\n".$signature;
      opMailSend::execute($subject, $memberPcAddress, $from, $body);
    }
    if (0 != $member->getConfig('ZUNIV_US_NOTIFYMOBILE',1) && $memberMobileAddress)
    {
      $signature = opMailSend::getMailTemplate('signature', 'mobile', array(), true, sfContext::getInstance());
      if ($signature) $signature = "\n".$signature;
      $body .= "\n".$signature;
      opMailSend::execute($subject, $memberMobileAddress, $from, $body);
    }
  }
}