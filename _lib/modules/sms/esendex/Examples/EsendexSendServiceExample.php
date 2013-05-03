<?php
/*
Name:			EsendexSendServiceExample.php
Description:	Example usage for the EsendexSendService class
Documentation: 	

Copyright (c) 2007 Esendex®

If you have any questions or comments, please email:

support@esendex.com
http://www.esendex.com/support

Esendex
http://www.esendex.com
*/

include_once( "../EsendexSendService.php" );

// Test Variables - assign values accordingly:
$username = "";			// Your Username (normally an email address).
$password = "";			// Your Password.
$accountReference = "";		// Your Account Reference (either your virtual mobile number, or EX account number).
$originator = "";		// An alias that the message appears to come from (alphanumeric characters only, and must be less than 11 characters).
$recipients = "";		// The mobile number(s) to send the message to (comma-separated).
$body = "";			// The body of the message to send (must be less than 160 characters).
$type = "Text";			// The type of the message in the body (e.g. Text, SmartMessage, Binary or Unicode).
$validityPeriod = 0;		// The amount of time in hours until the message expires if it cannot be delivered.
$result;			// The result of a service request.
$messageIDs = array();		// A single or comma-separated list of sent message IDs.
$messageStatus;			// The status of a sent message.

// Instantiate the service with login credentials.
$sendService = new EsendexSendService( $username, $password, $accountReference );

// Uncomment lines for different functions.

// Send a simple message.
print( "<b>SendMessage</b><br />" );
$result = $sendService->SendMessage( $recipients, $body, $type );

/*
// Send a message with a specified originator and validity period.
print( "<b>SendMessageFull</b><br />" );
$result = $sendService->SendMessageFull( $originator, $recipients, $body, $type, $validityPeriod );
*/

print_r( $result );

// Split the message IDs into an array.
$messageIDs = split( ",", $result['MessageIDs'] );

if ( !is_null( $messageIDs ) && sizeof( $messageIDs ) > 0 )
{
	print( "<br /><br />" );

	foreach ( $messageIDs as $messageID )
	{
		print( "<b>Message ID</b>: $messageID<br />" );
	}

	print( "<br /><hr /><br />" );

	// Get the status of the sent message(s).
	print( "<b>GetMessageStatus</b><br />" );
	foreach ( $messageIDs as $messageID )
	{
		$messageStatus = $sendService->GetMessageStatus( $messageID );
		
		print_r( $messageStatus );
		
		print( "<br /><br />" );
		
		print( "<b>$messageID</b>: ".$messageStatus['MessageStatus']."<br /><br />" );
	}
}
?>
