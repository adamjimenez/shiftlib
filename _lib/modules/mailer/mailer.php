<?
class mailer{
	function __construct($username,$password,$originator,$table) {
		$this->username=$username;
		$this->password=$password;
		$this->originator=$originator;
		$this->table=$table;
	}

	function send($users, $subject, $message)
	{
		$result = $this->send_email($users, $subject, $message);

		return $result;
	}
}
?>