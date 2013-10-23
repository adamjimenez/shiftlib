<?
/*
http://www.txtlocal.co.uk/api/
*/

class txtlocal extends sms
{
	function __construct($username,$password,$originator,$account,$table) {
		parent::__construct($username,$password,$originator,$account,$table);
	}

	function send_sms($recipients, $message)
	{
		// Configuration variables
		$info = "1";
		$test = "0";

		// Data for text message
		$from = $this->originator;
		$selectednums = $recipients; //comma separated list e.g.: 447xxxxxxxxx,447xxxxxxxxx
		$message = $message;
		$message = urlencode($message);

		// Prepare data for POST request
		$data = "uname=".$this->username."&pword=".$this->password."&message=".$message."&from=". $this->originator."&selectednums=".$selectednums."&info=".$info."&test=".$test."&custom=".$this->sent_id; // Send the POST request with cURL
		$ch = curl_init('http://www.txtlocal.com/sendsmspost.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$raw = curl_exec($ch); //This is the result from Txtlocal
		curl_close($ch);

		$arr=explode('<br>',$raw);

		foreach($arr as $k=>$v){
			$pair=explode('=',$v);
			$results[$pair[0]]=$pair[1];
		}

		if( !$result['Error'] ){
			return true;
		}else{
			return $result['Error'];
		}
	}

	function quota()
	{
		// Prepare data for POST request
		$data = "uname=".$this->username."&pword=".$this->password; // Send the POST request with cURL
		$ch = curl_init('http://www.txtlocal.com/getcredits.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$credits = curl_exec($ch); //This is the number of credits you have left
		curl_close($ch);

		return $credits;
	}
}
?>