<?
require('../../base.php');

if( $_POST['sender'] ){
	$registration=sql_query("SELECT * FROM registrations WHERE mobile='".escape($_POST['sender'])."'");

	sql_query("INSERT INTO sms_inbox SET
		mobile='".escape($_POST['sender'])."',
		recipient='".escape($_POST['inNumber'])."',
		message='".escape($_POST['content'])."',
		registration='".escape($registration[0]['id'])."'
	");
}
?>