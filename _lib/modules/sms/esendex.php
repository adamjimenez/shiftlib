<?
class esendex extends sms
{
	function __construct($username,$password,$originator,$account,$table) {
		parent::__construct($username,$password,$originator,$account,$table);

		require('_lib/sms/esendex/EsendexSendService.php');
		$this->sendService = new EsendexSendService($this->username, $this->password, $this->account);		
	}

	function send_sms($recipients, $message)
	{
		if($this->originator){
			$result=$this->sendService->SendMessageFull($this->originator,implode(',',$recipients), $message, 'Text',0);
		}else{
			$result=$this->sendService->SendMessage(implode(',',$recipients), $message, 'Text');
		}
		
		if( $result['Result']=='OK' ){
			return true;                
		}else{
			return $result;
		}
	}
	
	function quota()
	{
		return 1000;
	}
}
?>