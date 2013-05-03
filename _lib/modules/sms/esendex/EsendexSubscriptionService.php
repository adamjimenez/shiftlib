<?php
/*
Name:			EsendexSubscriptionService.php
Description:	Esendex SubscriptionService Web Service PHP Wrapper
Documentation: 	https://www.esendex.com/secure/messenger/formpost/StopSubscription.aspx

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

include_once( "EsendexFormPostUtilities.php" );

class EsendexSubscriptionService extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;

	function EsendexSubscriptionService( $username, $password, $accountReference, $isSecure = false, $certificate = '' )
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		$this->username = $username;
		$this->password = $password;
		$this->accountReference = $accountReference;

		if ( $isSecure )
		{
			define( "STOP_SUBSCRIPTION_URL", "https://www.esendex.com/secure/messenger/formpost/StopSubscription.aspx" );
		}
		
		else
		{
			define( "STOP_SUBSCRIPTION_URL", "http://www.esendex.com/secure/messenger/formpost/StopSubscription.aspx" );
		}
	}

	function StopSubscription( $mobileNumber = "", $successUrl = "", $failureUrl = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['mobileNumber'] = $mobileNumber;
		$parameters['successPage'] = $successUrl;
		$parameters['failurePage'] = $failureUrl;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, STOP_SUBSCRIPTION_URL );
	}
}
?>