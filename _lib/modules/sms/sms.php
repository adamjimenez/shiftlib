<?
class sms{
	function __construct($username,$password,$originator,$account,$table) {
		$this->username=$username;
		$this->password=$password;
		$this->originator=$originator;
		$this->account=$account;
		$this->table=$table;
	}
	
	function send($user_ids, $message, $custom=false)
	{
		$users=array();
		
		if( !$custom ){
			foreach( $user_ids as $id ){
				$select=mysql_query("SELECT * FROM ".$this->table." 
					WHERE 
						id='".escape($id)."'
					") or trigger_error("SQL", E_USER_ERROR);
				$users[]=mysql_fetch_array($select);
			}
		}else{
			$users=$user_ids;
		}
		
		mysql_query("INSERT INTO texts SET
			date=NOW(),
			message='".escape($message)."'
		") or trigger_error("SQL", E_USER_ERROR);
		
		$this->text_id=mysql_insert_id();
	
		foreach( $users as $user ){	
			$msg=$message;
			
			foreach( $user as $k=>$v ){
				$msg=str_replace('{$'.$k.'}',$v,$msg);
			}
			
			//die($msg);
		
			mysql_query("INSERT INTO texts_sent SET
				text='".escape($this->text_id)."',
				mobile='".escape($user['id'])."'
			") or trigger_error("SQL", E_USER_ERROR);
			
			$this->sent_id=mysql_insert_id();
	
			$result=$this->send_sms($user['mobile'], $msg);
		}

		return $result;
	}
}
?>