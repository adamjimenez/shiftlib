<?php
/*
Name:			EsendexSendContentService.php
Description:	Esendex SendContentService Web Service PHP Wrapper
Documentation: 	http://www.esendex.com/secure/messenger/formpost/SendWAPPushSMS.aspx

Copyright (c) 2004/2005 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

include_once( "EsendexFormPostUtilities.php" );

class EsendexSendContentService extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;

	function EsendexSendContentService( $username, $password, $accountReference, $isSecure = false, $certificate = '' )
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		$this->username = $username;
		$this->password = $password;
		$this->accountReference = $accountReference;

		if ( $isSecure )
		{
			define( "SEND_WAP_PUSH_SMS_URL", "https://www.esendex.com/secure/messenger/formpost/SendWAPPushSMS.aspx" );
		}
		else
		{
			define( "SEND_WAP_PUSH_SMS_URL", "http://www.esendex.com/secure/messenger/formpost/SendWAPPushSMS.aspx" );
		}
	}

	function SendWAPPush( $recipient, $href, $text )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['recipient'] = $recipient;
		$parameters['href'] = $href;
		$parameters['text'] = $text;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_WAP_PUSH_SMS_URL );
	}

	function SendWAPPushFull( $originator, $recipient, $href, $text, $validityPeriod )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['originator'] = $originator;
		$parameters['recipient'] = $recipient;
		$parameters['href'] = $href;
		$parameters['text'] = $text;
		$parameters['validityPeriod'] = $validityPeriod;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_WAP_PUSH_SMS_URL );
	}
}
?>