<?php
/*
Name:			EsendexSendService.php
Description:	Esendex SendService Web Service PHP Wrapper
Documentation: 	http://www.esendex.com/isSecure/messenger/formpost/SendServiceNoHeader.asmx
				http://www.esendex.com/isSecure/messenger/formpost/QueryStatus.aspx

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

include_once( "EsendexFormPostUtilities.php" );

class EsendexSendService extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;

	function EsendexSendService( $username, $password, $accountReference, $isSecure = false, $certificate = "" )
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		$this->username = $username;
		$this->password = $password;
		$this->accountReference = $accountReference;

		if ( $isSecure )
		{
			define( "SEND_SMS_URL", "https://www.esendex.com/secure/messenger/formpost/SendSMS.aspx" );
			define( "SMS_STATUS_URL", "https://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx" );
		}
		
		else
		{
			define( "SEND_SMS_URL", "http://www.esendex.com/secure/messenger/formpost/SendSMS.aspx" );
			define( "SMS_STATUS_URL", "http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx" );
		}
	}

	function SendMessage( $recipient, $body, $type )
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function SendMessageFull( $originator, $recipient, $body, $type, $validityPeriod )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['originator'] = $originator;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['validityPeriod'] = $validityPeriod;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function GetMessageStatus($messageID)
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['messageID'] = $messageID;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, SMS_STATUS_URL );
	}
}
?>
