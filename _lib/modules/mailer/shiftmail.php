<?
class shiftmail extends mailer
{
	function __construct($username,$password,$originator,$table) {
		parent::__construct($username,$password,$originator,$table);
	}

	function send_email($users, $subject, $msg)
	{

		// Prepare data for POST request
		$data = array(
			'user' => $this->username,
			'password' => $this->password,
			'originator' => $this->originator,
			'users' => serialize($users),
			'subject' => $subject,
			'body' => $msg,
		);

		$ch = curl_init('http://shiftmail.shiftcreate.com/rpc/send_email.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch); //This is the number of credits you have left
		curl_close($ch);

		return $result;
	}

	function quota()
	{
		// Prepare data for POST request
		$data = "user=".$this->username."&password=".$this->password; // Send the POST request with cURL
		$ch = curl_init('http://shiftmail.shiftcreate.com/rpc/quota.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$credits = curl_exec($ch); //This is the number of credits you have left
		curl_close($ch);

		return $credits;
	}

    function save_subscriber($subscriber, $group)
	{
    	// Prepare data for POST request
		$data = array(
			'user' => $this->username,
			'password' => $this->password,
			'subscriber' => $subscriber,
			'group' => $group
		);

		// Prepare data for POST request
		$ch = curl_init('http://shiftmail.shiftcreate.com/rpc/add_subscriber.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch); //This is the number of credits you have left
		curl_close($ch);

		return $result;
	}
}
?>