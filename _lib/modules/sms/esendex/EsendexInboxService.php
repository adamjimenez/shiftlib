<?php
/*
Name:			EsendexInboxService.php
Description:	Esendex InboxService Web Service PHP Wrapper
Documentation: 	http://www.esendex.com/isSecure/messenger/formpost/GetInboxMessage.aspx
				http://www.esendex.com/isSecure/messenger/formpost/DeleteInboxMessage.aspx

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

include_once( "EsendexFormPostUtilities.php" );

class EsendexInboxService extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;

	function EsendexInboxService($username, $password, $accountReference, $isSecure = false, $certificate = "" )
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		$this->username = $username;
		$this->password = $password;
		$this->accountReference = $accountReference;

		if ( $isSecure )
		{
			define( "GET_MESSAGES_URL", "https://www.esendex.com/secure/messenger/formpost/GetInboxMessage.aspx" );
			define( "GET_LATEST_MESSAGES_URL", "https://www.esendex.com/secure/messenger/formpost/GetLatestInboxMessages.aspx" );
			define( "DELETE_MESSAGE_URL", "https://www.esendex.com/secure/messenger/formpost/DeleteInboxMessage.aspx" );
		}
		else
		{
			define( "GET_MESSAGES_URL", "http://www.esendex.com/secure/messenger/formpost/GetInboxMessage.aspx" );
			define( "GET_LATEST_MESSAGES_URL", "http://www.esendex.com/secure/messenger/formpost/GetLatestInboxMessages.aspx" );
			define( "DELETE_MESSAGE_URL", "http://www.esendex.com/secure/messenger/formpost/DeleteInboxMessage.aspx" );
		}
	}

	function GetMessages()
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, GET_MESSAGES_URL );
	}
	
	function GetLatestMessages( $lastMessageID, $maxMessageCount )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['lastMessageID'] = $lastMessageID;
		$parameters['maxMessages'] = $maxMessageCount;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, GET_LATEST_MESSAGES_URL );
	}

	function DeleteMessage( $messageID )
	{

		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['messageID'] = $messageID;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, DELETE_MESSAGE_URL );
	}
}
?>