<?php
/*
Name:			EsendexSendContentServiceExample.php		
Description:	Example usage for the EsendexSendContentService class
Documentation: 	

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support

Esendex
http://www.esendex.com
*/

include_once( "../EsendexSendContentService.php" );

// Test Variables - assign values accordingly:
$username = "";			// Your Username (normally an email address).
$password = "";			// Your Password.
$accountReference = " ";	// Your Account Reference (either your virtual mobile number, or EX account number).
$originator = "";		// An alias that the message appears to come from (alphanumeric characters only, and must be less than 11 characters).
$recipients = "";		// The mobile number(s) to send the message to (comma separated).
$href = "";			// The Link to send, e.g. http://wap.yahoo.com.
$text = "Look at this!";	// A Description of the Link, e.g. Yahoo WAP Site.
$validityPeriod = 0;		// The amount of time in hours until the message expires if it cannot be delivered.
$result;			// The result of a service request.

// Instantiate the service with login credentials.
$sendContentService = new EsendexSendContentService( $username, $password, $accountReference );

// Uncomment lines for different functions.

// Send a simple WAP push message.
print( "<b>SendWAPPush</b><br />" );
$result = $sendContentService->SendWAPPush( $recipients, $href, $text );

/*
// Send a WAP push message with a specified alias and validity period
print( "<b>SendWAPPushFull</b><br />" );
$result = $sendContentService->SendWAPPushFull( $originator, $recipients, $href, $text, $validityPeriod );
*/

print_r( $result );

// Split the message IDs into an array.
$messageIDs = array();
$messageIDs = split( ",", $result['MessageIDs'] );

if ( !is_null( $messageIDs ) && sizeof( $messageIDs ) > 0 )
{
	print "<br /><br />";
	
	foreach ( $messageIDs as $messageID )
	{
		print "<b>Message ID</b>: $messageID<br />";
	}
}
?>