<?
global $mailer_config;

require('_lib/modules/mailer/mailer.php');
require('_lib/modules/mailer/shiftmail.php');

$valid_users=array();
foreach( $users as $user ){
	if( is_email($user['email']) ){
		$valid_users[]=$user;
	}
}

$_SESSION['valid_users'] = $valid_users;

$sms = new shiftmail($mailer_config['username'], $mailer_config['password'], $mailer_config['originator'], underscored($this->section));

$credits=$sms->quota(); //we need to obtain this somehow

//sms templates
$select=mysql_query("SELECT * FROM email_templates ORDER BY subject");
while( $row=mysql_fetch_array($select) ){
	$opts['email_template'][$row['id']]=$row['subject'];
	$email_templates[$row['id']]=$row;
}

if( !is_numeric($credits) ){
//	die('error: no credits -'.$credits);
}

if( $_POST['message'] ){
	$users = $_SESSION['valid_users'];


	//remove first 997
	//$users = array_slice($users,997);


	//print_r($_SESSION['valid_users']);
	//exit;

	/*
	$recipients=array();

	foreach( $_POST['users'] as $k=>$v ){
		$recipients[]=$v;
	}

	$_POST['sms_message']=$_POST['sms_message'];

	//print_r($recipients);	exit;

	//print_r($result);
	*/

	$result=$sms->send($users, $_POST['subject'], $_POST['message']);
	//$result=true;

	$_SESSION['message']=$result;

	//unset($_POST);
	redirect('?'.$_SERVER['QUERY_STRING']);

}elseif( $_POST['users'] and count($_POST['users'])>$credits ){
?>
<p>Insufficient credits. <a href="http://account.shiftcreate.com/order/?p=credits" target="_blank">Buy some more</a> or select fewer recipients.</p>
<?
}else{

?>

<script type="text/javascript">
email_templates=<?=json_encode($email_templates);?>;
</script>

<div id="container">
<h1>Send Email</h1>

<p>Currently have <strong><?=$credits;?></strong> credits remaining</p>
<p>About to send <?=count($valid_users);?> email<? if( count($users)>1 ){ ?>s<? } ?></p>
<br />

<form method="post" id="form">
<input type="hidden" name="shiftmail" value="1">

<!--
Send this message:<br>
<select id="email_template">
	<option value=""></option>
	<?=html_options($opts['email_template']);?>
</select><br />
<br />
-->
Subject<br />
<input type="text" id="subject" name="subject" value="<?=$_POST['subject'];?>" size="80" /><br>
<br />
Message<br />
<textarea class="tinymce" name="message" rows="20" cols="80"><?=$_POST['message'];?></textarea><br>
<br>
<br>
<button type="submit">Send</button><br>
</form>

</div>
<?
}
?>