<?
require('../../base.php');

$result_opts=array(
	'D'=>'active',
	'I'=>'invalid',
	'U'=>'unavailable',
);

if( $_POST['number'] and $_POST['status'] ){	
	sql_query("UPDATE texts_sent SET
		result='".escape($result_opts[$_POST['status']])."'
		WHERE
			id='".escape($_POST['customID'])."'
	");
	
	/*
	foreach( $_POST as $k=>$v ){
		$msg.=$k.': '.$v."\n";
	}
	
	mail('adam@shiftcreate.com','mobile receipt',$msg,"From:auto@shiftcreate.com");
	*/
}
?>