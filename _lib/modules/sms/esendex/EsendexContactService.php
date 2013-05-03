<?php
/*
Name:			EsendexContactService.php.
Description:	Esendex ContactService Web Service PHP Wrapper.
Documentation: 	

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

include_once( "EsendexFormPostUtilities.php" );

class EsendexContactService extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;

	function EsendexContactService( $username, $password, $accountReference, $isSecure = false, $certificate = "" )
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		$this->username = $username;
		$this->password = $password;
		$this->accountReference = $accountReference;

		if ( $isSecure )
		{	
			define( "ADD_CONTACT_URL", "https://www.esendex.com/secure/messenger/formpost/AddContact.aspx" );
			define( "GET_CONTACT_URL", "https://www.esendex.com/secure/messenger/formpost/GetContact.aspx" );
			define( "UPDATE_CONTACT_URL", "https://www.esendex.com/secure/messenger/formpost/UpdateContact.aspx" );
			define( "DELETE_CONTACT_URL", "https://www.esendex.com/secure/messenger/formpost/DeleteContact.aspx" );
		
			define( "ADD_GROUP_URL", "https://www.esendex.com/secure/messenger/formpost/AddContactGroup.aspx" );
			define( "GET_GROUP_URL","https://www.esendex.com/secure/messenger/formpost/GetContactGroup.aspx" );
			define( "GET_GROUP_MEMBERS_URL", "https://www.esendex.com/secure/messenger/formpost/GetContactGroupMembers.aspx" );
			define( "UPDATE_GROUP_URL", "https://www.esendex.com/secure/messenger/formpost/UpdateContactGroup.aspx" );
			define( "DELETE_GROUP_URL", "https://www.esendex.com/secure/messenger/formpost/DeleteContactGroup.aspx" );
		}
		
		else
		{
			define( "ADD_CONTACT_URL", "http://www.esendex.com/secure/messenger/formpost/AddContact.aspx" );
			define( "GET_CONTACT_URL", "http://www.esendex.com/secure/messenger/formpost/GetContact.aspx" );
			define( "UPDATE_CONTACT_URL", "http://www.esendex.com/secure/messenger/formpost/UpdateContact.aspx" );
			define( "DELETE_CONTACT_URL", "http://www.esendex.com/secure/messenger/formpost/DeleteContact.aspx" );
		
			define( "ADD_GROUP_URL", "http://www.esendex.com/secure/messenger/formpost/AddContactGroup.aspx" );
			define( "GET_GROUP_URL","http://www.esendex.com/secure/messenger/formpost/GetContactGroup.aspx" );
			define( "GET_GROUP_MEMBERS_URL", "http://www.esendex.com/secure/messenger/formpost/GetContactGroupMembers.aspx" );
			define( "UPDATE_GROUP_URL", "http://www.esendex.com/secure/messenger/formpost/UpdateContactGroup.aspx" );
			define( "DELETE_GROUP_URL", "http://www.esendex.com/secure/messenger/formpost/DeleteContactGroup.aspx" );
		}
	}
	
	function AddContact( $quickName, $mobileNumber, $contactID = "", $firstName = "", $lastName = "", $telephoneNumber = "", $streetAddress1 = "", $streetAddress2 = "", $town = "", $county = "", $postcode = "", $country = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['quickName'] = $quickName;
		$parameters['mobileNumber'] = $mobileNumber;
		$parameters['contactID'] = $contactID;
		$parameters['firstname'] = $firstName;
		$parameters['lastName'] = $lastName;
		$parameters['telephoneNumber'] = $telephoneNumber;
		$parameters['streetAddress1'] = $streetAddress1;
		$parameters['streetAddress2'] = $streetAddress2;
		$parameters['town'] = $town;
		$parameters['county'] = $county;
		$parameters['postcode'] = $postcode;
		$parameters['country'] = $country;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, ADD_CONTACT_URL );
	}
	
	function GetContact( $contactID = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['contactID'] = $contactID;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, GET_CONTACT_URL );
	}
	
	function UpdateContact( $contactID, $quickName, $mobileNumber, $firstName = "", $lastName = "", $telephoneNumber = "", $streetAddress1 = "", $streetAddress2 = "", $town = "", $county = "", $postcode = "", $country = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['contactID'] = $contactID;
		$parameters['quickName'] = $quickName;
		$parameters['mobileNumber'] = $mobileNumber;
		$parameters['firstname'] = $firstName;
		$parameters['lastName'] = $lastName;
		$parameters['telephoneNumber'] = $telephoneNumber;
		$parameters['streetAddress1'] = $streetAddress1;
		$parameters['streetAddress2'] = $streetAddress2;
		$parameters['town'] = $town;
		$parameters['county'] = $county;
		$parameters['postcode'] = $postcode;
		$parameters['country'] = $country;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, UPDATE_CONTACT_URL );
	}
	
	function DeleteContact( $contactID )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['contactID'] = $contactID;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, DELETE_CONTACT_URL );
	}
	
	function AddGroup( $name, $groupID = "", $description = "", $memberIDs = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['name'] = $name;
		$parameters['groupID'] = $groupID;
		$parameters['description'] = $description;
		$parameters['memberIDs'] = $memberIDs;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, ADD_GROUP_URL );
	}
	
	function GetGroup( $groupID = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['groupID'] = $groupID;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, GET_GROUP_URL );
	}
	
	function UpdateGroup( $groupID, $name, $description = "", $memberIDs = "" )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['groupID'] = $groupID;
		$parameters['name'] = $name;
		$parameters['description'] = $description;
		$parameters['memberIDs'] = $memberIDs;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, UPDATE_GROUP_URL );
	}
	
	function GetGroupMembers( $groupID )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['groupID'] = $groupID;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, GET_GROUP_MEMBERS_URL );
	}
	
	function DeleteGroup( $groupID )
	{
		$parameters['username'] = $this->username;
		$parameters['password'] = $this->password;
		$parameters['account'] = $this->accountReference;
		$parameters['groupID'] = $groupID;
		
		$parameters['plainText'] = "1";
		
		return $this->FormPost( $parameters, DELETE_GROUP_URL );
	}
}
?>