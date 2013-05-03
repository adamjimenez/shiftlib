<?php
/*
Name:			EsendexSendService.php
Description:	Esendex SendService Web Service PHP Wrapper
Documentation: 	http://www.esendex.com/secure/messenger/formpost/SendServiceNoHeader.asmx
			http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx

Copyright (c) 2004/2005 Esendex

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

require(dirname(__FILE__).'/EsendexFormPostUtils.php');

class EsendexSendService
{
	var $username;
	var $password;
	var $account;
	var $secure = false;
	var $certificate = '';

	function EsendexSendService($username, $password, $account, $issecure = false, $cert = '')
	{
		$this->username = $username;
		$this->password = $password;
		$this->account = $account;

		//suppress warnings from nusoap
		error_reporting(error_reporting() & ~E_NOTICE);

		//set URI of send service WSDL
		if ($issecure === true)
		{
			define('SEND_SMS_URL', 'https://www.esendex.com/secure/messenger/formpost/SendSMS.aspx');
			define('STATUS_SMS_URL', 'https://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx');
			$this->certificate = $cert;
			$this->secure = true;
		}
		else
		{
			define('SEND_SMS_URL', 'http://www.esendex.com/secure/messenger/formpost/SendSMS.aspx');
			define('STATUS_SMS_URL', 'http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx');
			$secure = false;
		}
	}

	function SendMessageFull($originator, $recipient, $body, $type, $validityPeriod)
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->account;
		$parameters['originator'] = $originator;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['validityperiod'] = $validityPeriod;
		$parameters['plaintext'] = '1';


		$formpost = new EsendexFormPostUtils( );
		return $formpost->FormPostFull($parameters, SEND_SMS_URL, $this->secure, $this->certificate );
	}

	function SendMessage($recipient, $body, $type)
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->account;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['plaintext'] = '1';

		$formpost = new EsendexFormPostUtils( );
		return $formpost->FormPostFull($parameters, SEND_SMS_URL, $this->secure, $this->certificate);
	}

	function GetMessageStatus($messageID)
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->account;
		$parameters['messageid'] = $messageID;
		$parameters['plaintext'] = '1';

		$formpost = new EsendexFormPostUtils( );
		return $formpost->FormPostFull($parameters, STATUS_SMS_URL, $this->secure, $this->certificate);
	}
}
?>
