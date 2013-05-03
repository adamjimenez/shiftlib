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
		/*
		foreach( $user_ids as $id ){
			$select=mysql_query("SELECT * FROM ".$this->table."
				WHERE
					id='".escape($id)."'
				") or trigger_error("SQL", E_USER_ERROR);;
			$users[]=mysql_fetch_array($select);
		}*/

		$result=$this->send_email($users, $subject, $message);

		return $result;
	}
}
?>