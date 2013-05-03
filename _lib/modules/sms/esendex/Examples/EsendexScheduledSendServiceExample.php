<?php
/*
Name:			EsendexScheduledSendServiceExample.php
Description:	Example usage for the EsendexScheduledSendService class
Documentation: 	

Copyright (c) 2007 Esendex®

If you have any questions or comments, please email:

support@esendex.com
http://www.esendex.com/support

Esendex
http://www.esendex.com
*/

include_once( "../EsendexScheduledSendService.php" );

//Test Variables - assign values accordingly:
$username = "";				// Your Username (normally an email address).
$password = "";				// Your Password.
$accountReference = "";			// Your Account Reference (either your virtual mobile number, or EX account number).
$originator = "";			// An alias that the message appears to come from (alphanumeric characters only, and must be less than 11 characters).
$recipients = "";			// The mobile number(s) to send the message to (comma separated).
$body = "";				// The body of the message to send (must be less than 160 characters).
$type = "Text";				// The type of the message in the body (e.g. Text, SmartMessage, Binary or Unicode).
$validityPeriod = 0;			// The amount of time in hours until the message expires if it cannot be delivered.
$submitAt = "9999-12-31T23:59:59";	// The date/time the message will be sent at in the format: CCYY-MM-DDThh:mm:ss.
$days = 365;				// The time interval in days before the message is sent.
$hours = 59;				// The time interval in hours before the message is sent.
$minutes = 59;				// The time interval in minutes before the message is sent.
$result;				// The result of a service request.
$messageIDs = array();			// A comma-separated string of sent message IDs.
$messageStatus;				// The status of a sent message.

// Instantiate the service with login credentials.
$scheduledSendService = new EsendexScheduledSendService( $username, $password, $accountReference );

// Uncomment lines for different functions.

// Send a simple scheduled message with a specified date/time.
print( "<b>ScheduledSendMessageAt</b><br />" );
$result = $scheduledSendService->ScheduledSendMessageAt( $recipients, $body, $type, $submitAt );

/*
// Send a simple scheduled message with a specified count down time in days, hours and minutes.
print( "<b>ScheduledSendMessageIn</b><br />" );
$result = $scheduledSendService->ScheduledSendMessageIn( $recipients, $body, $type, $days, $hours, $minutes );

// Send a scheduled message with a specified originator and validity period.
print( "<b>ScheduledSendMessageAtFull</b><br />" );
$result = $scheduledSendService->ScheduledSendMessageAtFull( $originator, $recipients, $body, $type, $validityPeriod, $submitAt );

// Send a scheduled message with a specified originator, validity period and count down time in days, hours and minutes.
print( "<b>ScheduledSendMessageInFull</b><br />" );
$result = $scheduledSendService->ScheduledSendMessageInFull( $originator, $recipients, $body, $type, $validityPeriod, $days, $hours, $minutes );
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
	print ( "<b>GetMessageStatus</b><br />" );
	foreach ( $messageIDs as $messageID )
	{
		$messageStatus = $scheduledSendService->GetMessageStatus( $messageID );
		
		print_r( $messageStatus );
		
		print( "<br /><br />" );
		
		print( "<b>$messageID</b>: ".$messageStatus['MessageStatus']."<br /><br />" );
	}
}
?>
