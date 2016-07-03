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
				$users[] = sql_query("SELECT * FROM ".$this->table." 
					WHERE 
						id='".escape($id)."'
					", 1);
			}
		}else{
			$users=$user_ids;
		}
		
		sql_query("INSERT INTO texts SET
			date=NOW(),
			message='".escape($message)."'
		");
		
		$this->text_id = sql_insert_id();
	
		foreach( $users as $user ){	
			$msg=$message;
			
			foreach( $user as $k=>$v ){
				$msg=str_replace('{$'.$k.'}',$v,$msg);
			}
			
			//die($msg);
		
			sql_query("INSERT INTO texts_sent SET
				text='".escape($this->text_id)."',
				mobile='".escape($user['id'])."'
			");
			
			$this->sent_id = sql_insert_id();
	
			$result=$this->send_sms($user['mobile'], $msg);
		}

		return $result;
	}
}
?>