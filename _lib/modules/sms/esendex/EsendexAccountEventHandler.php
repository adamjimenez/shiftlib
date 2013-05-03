<?php
/*
Name:			EsendexAccountEventHandler.php
Description:	HTTP Form Post account event handler for PHP
Documentation: 	https://www.esendex.com/secure/messenger/formpost/AccountEventHandler.aspx

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support

Detail:
This code can be used as a base for creating a Form Post PHP Account Event Handler. Place code 
in the TODO sections to handle the different notifications as required.

Once you have deployed the page you need to specify the URL to it in the Options page of your 
account.  This piece of code will give you a page that can accept posts that are FormPostVersion2.
*/

$username = GetFormVariable( "username" ) ;
$password = GetFormVariable( "password" ) ;

/*
 *	TODO: Authenticate user and password if required.
 */

$notificationType = GetFormVariable( "notificationType" ) ;

switch ( $notificationType )
{
	case "MessageReceived":
		HandleMessageReceived();
		break ;
		
	case "MessageEvent" :
		HandleMessageEvent() ; 
		break ;
		
	case "MessageError" :
		HandleMessageError() ; 
		break ;
		
	case "SubscriptionEvent" :
		HandleSubscriptionEvent() ; 
		break ;
		
	default :
		/*
		 *	The page received an unhandled type.
		 *
		 *	TODO: Add code to report unhandled types if required.
		 */
		break;
}

/*
 *	Retrieves the form variable with the given name, returning 
 *	the defaultValue if the form variable is not set.
 *
 *	Set $echoValue to true ONLY for testing.  If set to true the value 
 *	read will be output.
 */
function GetFormVariable( $formName, $defaultValue = "", $echoValue = false )
{	
	$returnedValue = ( isset( $_POST[$formName] ) ) ? $_POST[$formName] : $defaultValue;
	
	if ( $echoValue )
	{
		echo $formName." = [".$returnedValue."]";
	}
	
	return $returnedValue;
}

// MessageReceived event.
function HandleMessageReceived()
{	
	$inboundMessageID = GetFormVariable( "id" );
	$originator = GetFormVariable( "originator" );
	$recipient = GetFormVariable( "recipient" );
	$body = GetFormVariable( "body" );
	$messageType = GetFormVariable( "type" );
	$receivedAt = GetFormVariable( "receivedAt" );

	/*
	 *	TODO: Add code for when a message is received.
	 */
}

// MessageEvent event.
function HandleMessageEvent()
{
	$eventMessageID = GetFormVariable( "id" );
	$eventType = GetFormVariable( "eventType" );
	$eventOccurredAt = GetFormVariable( "occurredAt" );

	/*
	 *	TODO: Add code for when a message event is fired.
	 */
}

// MessageError event.
function HandleMessageError()
{
	$errorMessageID = GetFormVariable( "id" );
	$errorType = GetFormVariable( "errorType" );
	$errorOccurredAt = GetFormVariable( "occurredAt" );
	$errorDetail = GetFormVariable( "detail" );

	/*
	 *	TODO: Add code to handle message errors.
	 */
}

// Subscription event.
function HandleSubscriptionEvent()
{
	$mobileNumber = GetFormVariable( "mobileNumber" );
	$contactID = GetFormVariable( "contactID" );
	$subcriptionEventOccurredAt = GetFormVariable( "occurredAt" );
	$eventType = GetFormVariable( "eventType" );

	/*
	 *	TODO: Add code to handle premium rate subscription events
	 *	(Only needed for premium rate accounts).
	 */
}

?>