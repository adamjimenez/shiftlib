<?php
/*
Name:			EsendexInboxServiceExample.php		
Description:	Example usage for the EsendexInboxService class
Documentation: 	

Copyright © 2007 Esendex

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support

Esendex
http://www.esendex.com
*/

include_once( "../EsendexInboxService.php" );

// Test Variables - assign values accordingly:
$username = "";			// Your Username (normally an email address).
$password = "";			// Your Password.
$accountReference = "";		// Your Account Reference (either your virtual mobile number, or EX account number).
$result;			// The result of a service request.
$lastMessageID = "";		// The last message ID (optional).
$maximumMessageCount;		// Maximum number of messages that will be returned.
$messageID;			// A message ID (GUID).

// Instantiate the service with login credentials.
$inboxService = new EsendexInboxService( $username, $password, $accountReference );

// Uncomment lines for different functions.

// Get all messages from the inbox.
print "<b>GetMessages</b></br >";
$result = $inboxService->GetMessages();
print_r( $result );

$messages = $result['Messages'];

/*
// Get the latest messages from the inbox.
print "<b>GetLatestMessages</b></br >";
$result = $inboxService->GetLatestMessages( $lastMessageID, $maximumMessageCount );
print_r( $result );
*/

if ( !is_null( $messages ) )
{
	print "<br /><br />";

	foreach ( $messages as $message )
	{
		foreach ( $message as $key => $value )
		{
			print "<b>$key</b>: $value<br />";
		}
		
		print "<br />";
	}
}

/*
print "<br /><hr /><br />";

// Delete a message from the inbox.
$result = $inboxService->DeleteMessage( $messageID );

print_r( $result );
*/
?>