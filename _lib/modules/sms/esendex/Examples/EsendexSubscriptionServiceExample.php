<?php
/*
Name:			EsendexSubscriptionServiceExample.php		
Description:	Example usage for the EsendexSubscriptionService class
Documentation: 	

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support

Esendex
http://www.esendex.com
*/

include_once( "../EsendexSubscriptionService.php" );

// Test Variables - assign values accordingly:
$username = "";		// Your Username (normally an email address).
$password = "";		// Your Password.
$accountReference = "";	// Your Account Reference (either your virtual mobile number, or EX account number).
$mobileNumber = "";	// The mobile number subscribed to a service.
$result;		// The result of a service request.

// Instantiate the service with login credentials.
$subscriptionService = new EsendexSubscriptionService( $username, $password, $accountReference );

// Uncomment lines for different functions.

// Stop a subscription for a specified mobile number.
print( "<b>Stop Subscription</b><br />" );
$result = $subscriptionService->StopSubscription( $mobileNumber );

print_r( $result );
?>