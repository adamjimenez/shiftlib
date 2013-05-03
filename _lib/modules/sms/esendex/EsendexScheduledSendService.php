<?php

/*
Name:			EsendexScheduledSendService.php
Description:	Esendex SendService Web Service PHP Wrapper
Documentation: 	http://www.esendex.com/secure/messenger/formpost/ScheduledSendSMS.aspx
				http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

include_once( "EsendexFormPostUtilities.php" );

class EsendexScheduledSendService extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;

	function EsendexScheduledSendService($username, $password, $accountReference, $isSecure = false, $certificate = "" )
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		$this->username = $username;
		$this->password = $password;
		$this->accountReference = $accountReference;

		if ( $isSecure )
		{
			define( "SEND_SMS_URL", "https://www.esendex.com/secure/messenger/formpost/ScheduledSendSMS.aspx" );
			define( "SMS_STATUS_URL", "https://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx" );
		}
		else
		{
			define( "SEND_SMS_URL", "http://www.esendex.com/secure/messenger/formpost/ScheduledSendSMS.aspx" );
			define( "SMS_STATUS_URL", "http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx" );
		}
	}

	function ScheduledSendMessageAt( $recipient, $body, $type, $submitAt )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['submitAt'] = $submitAt;
		$parameters['plaintext'] = "1";

		return $this->FormPost($parameters, SEND_SMS_URL );
	}

	function ScheduledSendMessageAtFull( $originator, $recipient, $body, $type, $validityPeriod, $submitAt )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['originator'] = $originator;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['validityPeriod'] = $validityPeriod;
		$parameters['submitAt'] = $submitAt;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function ScheduledSendMessageIn( $recipient, $body, $type, $days, $hours, $minutes )
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['days'] = $days;
		$parameters['hours'] = $hours;
		$parameters['minutes'] = $minutes;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function ScheduledSendMessageInFull( $originator, $recipient, $body, $type, $validityPeriod, $days, $hours, $minutes )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['originator'] = $originator;
		$parameters['recipient'] = $recipient;
		$parameters['body'] = $body;
		$parameters['type'] = $type;
		$parameters['validityPeriod'] = $validityPeriod;
		$parameters['days'] = $days;
		$parameters['hours'] = $hours;
		$parameters['minutes'] = $minutes;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function GetMessageStatus( $messageID )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['messageID'] = $messageID;
		
		$parameters['plainText'] = "1";

		return $this->FormPost($parameters, SMS_STATUS_URL );
	}
}
?>
