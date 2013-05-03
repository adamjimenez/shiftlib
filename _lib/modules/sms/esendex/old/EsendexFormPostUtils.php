<?php

/*
Name:			FormPostUtils.php
Description:	Esendex PHP HTTP Form Post Utilities
Documentation: 	https://www.esendex.com/secure/messenger/formpost/SendServiceNoHeader.asmx

Copyright (c) 2004/2005 Esendex

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

class EsendexFormPostUtils
{
	function EsendexFormPostUtils()
	{
	}


	function FormPostFull( $datastream, $url, $secure, $certificatefile )
	{

		$reqbody = "";
		$response = "";
		$port = 80;

		foreach($datastream as $key=>$val)
		{
			if( $key != '' && $val != '' )
			{
				if (!empty($reqbody)) $reqbody.= "&";
				$reqbody.= $key."=".urlencode(utf8_encode($val));
			}
		}
		
		$ch = curl_init();    							// initialize curl handle
		curl_setopt($ch, CURLOPT_URL,$url); 			// set url to post to
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);	// allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 	// return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 			// times out after 30s

		if ($secure == true)
		{
			curl_setopt($ch, CURLOPT_CAINFO, $certificatefile );
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);

			$port = 443;
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_PORT, $port);			//Set the port number

		curl_setopt($ch, CURLOPT_POST, 1); 				// set POST method
		curl_setopt($ch, CURLOPT_POSTFIELDS, $reqbody); // add POST fields
		$result = curl_exec($ch); 						// run the whole process

		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_close($ch);

		return $this->ParseResult( $result );
	}


	function FormPost( $datastream, $url)
	{
		return $this->FormPostFull($datastream, $url, false, '');
	}


	function ParseResult( $result )
	{
		
		$results = explode( "\r\n", $result );
		$index = count($results);

		$i = 0;
		$j = 0;

		while( $i < $index )
		{
			$andpos = strpos($results[$i], "&");

			if( $andpos != false )
			{
				$values[$j] = explode( "&", $results[$i] );
				$results[$i] = $this->FormKeyValuePairs( $values[$j] );
				$j++;
			}
			$i++;
		}

		//Get the message and key value pair elements from the results.
		$messages = $this->GetMessagesArrays( $results );
		$keyvals = $this->FormKeyValuePairs( $results );

		if( is_array( $messages ) == false )
		{
			return $keyvals;
		}

		$keyvals[ "Messages" ] = $messages;
		return $keyvals;
	}


	function FormKeyValuePairs( $results )
	{
		$i = 0;
		$j = 0;
		$response = '';
		$index = count($results);

		while( $i < $index )
		{
			if( is_array( $results[ $i ] ) == false)
			{
				$equalspos = strpos( $results[$i], "=" );

				if( $equalspos != false )
				{
					$reskey = substr( $results[$i], 0, strpos( $results[$i], "=" ) );
					$resvalue = urldecode( substr( $results[$i], $equalspos + 1, strlen( $results[$i] ) - $equalspos - 1 ) );

					$response[ $reskey ] = $resvalue;
				}
			}
			$i++;
		}
		return $response;
	}


	function GetMessagesArrays( $results )
	{
		$i = 0;
		$j = 0;
		$index = count($results);
		$messages = '';

		while( $i < $index )
		{
			if( is_array( $results[ $i ] ) == true )
			{
				$messages[$j] = $results[$i];
				$j++;
			}
			$i++;
		}

		$result = '';
		if( $j > 0 )
		{
			$result = $messages;
		}
		return $result;
	}
}
?>