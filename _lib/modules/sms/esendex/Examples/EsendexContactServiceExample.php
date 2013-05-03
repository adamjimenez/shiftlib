<?php
/*
Name:			EsendexContactServiceExample.php		
Description:	Example usage for the EsendexContactService class
Documentation: 	

Copyright © 2007 Esendex

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support

Esendex
http://www.esendex.com
*/

include_once( "../EsendexContactService.php" );

// Test Variables - assign values accordingly:
$username = "";				// Your Username (normally an email address).
$password = "";				// Your Password.
$accountReference = "";			// Your Account Reference (either your virtual mobile number, or EX account number).
$result;				// The result of a service request.
$contactID = array();			// A contact ID (GUID).
$contactName = "";			// A contact name.
$mobileNumber = "";			// A mobile number.
$groupID;				// A group ID (GUID).
$groupName = "myGroup";			// A group name.
$groupMembers = array();		// An array of group members.

$contactService = new EsendexContactService( $username, $password, $accountReference );

// **********

// Add a contact with the minimum details.
print "<b>AddContact</b><br />";
$result = $contactService->AddContact( $contactName, $mobileNumber );
print_r( $result );

print "<br />";

// Extract the contact ID from the result.
$contactID = $result['ContactID'];

print "<b>Contact ID</b>: $contactID";

print "<br /><br /><hr /><br />";

// *********

// Get all of the information for a contact.
print "<b>GetContact</b><br />";
$result = $contactService->GetContact( $contactID );
print_r( $result );

print "<br /><br />";

$contacts = $result['Messages'];

foreach ( $contacts as $contact )
{
	foreach ( $contact as $key => $value )
	{
		print "<b>$key</b>: $value<br />";
	}
	
	print "<br />";
}

print "<hr /><br />";

// *********

// Update a contact with minimum details.
print "<b>UpdateContact</b><br />";
print_r( $contactService->UpdateContact( $contactID, "myOtherContact", "9876543210" ) );

print "<br /><br /><hr /><br />";

// *********

// Get all of the contacts by not specifying a contact ID.
print "<b>GetContact</b><br />";
$result = $contactService->GetContact();
print_r( $result );

print "<br /><br />";

$contacts = $result['Messages'];

foreach ( $contacts as $contact )
{
	foreach ( $contact as $key => $value )
	{
		print "<b>$key</b>: $value<br />";
	}
	
	print "<br />";
}

print "<hr /><br />";

// *********

// Add a group with the minimum details.
print "<b>AddGroup</b><br />";
$result = $contactService->AddGroup( $groupName );
print_r( $result );

print "<br />";

// Extract the group ID from the result.
$groupID = $result['GroupID'];

print "<b>Group ID</b>: $groupID";

print "<br /><br /><hr /><br />";

// *********

// Get all of the information for a group.
print "<b>GetGroup</b><br />";
$result = $contactService->GetGroup( $groupID );
print_r( $result );

print "<br /><br />";

$groups = $result['Messages'];

foreach ( $groups as $group )
{
	foreach ( $group as $key => $value )
	{
		print "<b>$key</b>: $value<br />";
	}
	
	print "<br />";
}

print "<hr /><br />";

// *********

// Update a group with a new name, description and contact member IDs.
print "<b>UpdateGroup</b><br />";
print_r( $contactService->UpdateGroup( $groupID, "myOtherGroup", "myDescription", $contactID ) );

print "<br /><br /><hr /><br />";

// *********

// Get all of the groups by not specifying a group ID.
print "<b>GetGroup</b><br />";
$result = $contactService->GetGroup();
print_r( $result );

print "<br /><br />";

$groups = $result['Messages'];

foreach ( $groups as $group )
{
	foreach ( $group as $key => $value )
	{
		print "<b>$key</b>: $value<br />";
	}
	
	print "<br />";
}

print "<hr /><br />";

// *********

// Get all of the contacts associated with a group.
print "<b>GetGroupMembers</b><br />";
$result = $contactService->GetGroupMembers( $groupID );
print_r( $result );

$groupMembers = array();
$groupMembers = $result['Messages'];

print "<br /><br />";

foreach ( $groupMembers as $groupMember )
{
	foreach ( $groupMember as $key => $value )
	{
		print "<b>$key</b>: $value<br />";
	}
	
	print "<br />";
}

print "<hr /><br />";

// *********

// Delete a contact.
print "<b>DeleteContact</b><br />";
print_r( $contactService->DeleteContact( $contactID ) );

print "<br /><br /><hr /><br />";

// *********

// Delete a group.
print "<b>DeleteGroup</b><br />";
print_r( $contactService->DeleteGroup( $groupID ) );
?>