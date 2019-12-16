<?php
global $sms_config;

require('_lib/modules/sms/sms.php');
require('_lib/modules/sms/' . $sms_config['provider'] . '.php');

$valid_users = [];
foreach ($users as $user) {
    if (format_mobile($user['mobile'])) {
        $valid_users[] = $user;
    }
}

$sms = new $sms_config['provider']($sms_config['username'], $sms_config['password'], $sms_config['originator'], $sms_config['account'], underscored($this->section));

$credits = $sms->quota(); //we need to obtain this somehow

//sms templates
$rows = sql_query('SELECT * FROM sms_templates ORDER BY subject');
foreach ($rows as $row) {
    $opts['sms_template'][$row['id']] = $row['subject'];
    $sms_templates[$row['id']] = $row;
}

if (!is_numeric($credits)) {
    die('error: ' . $credits);
}

if ($_POST['sms_message']) {


    /*
    $recipients=array();

    foreach( $_POST['users'] as $k=>$v ){
        $recipients[]=$v;
    }

    $_POST['sms_message']=$_POST['sms_message'];

    //print_r($recipients);	exit;

    //print_r($result);
    */

    $result = $sms->send($_POST['users'], $_POST['sms_message']);
    //$result=true;

    if ($result) {
        $_SESSION['message'] = count($users) . ' message(s) sent';
    } else {
        $_SESSION['message'] = $result;

        print_r($result);
    } ?>
	<p><a href="?option=texts&view=true&id=<?=$sms->text_id; ?>">View text</a></p>
	<p><a href="?<?=$_SERVER['QUERY_STRING']; ?>">Go back</a></p>
	<?php
} elseif ($_POST['users'] and count($_POST['users']) > $credits) {
        ?>
<p>Insufficient credits. Buy some more or select fewer recipients.</p>
<?php
    } else {
        ?>
<link rel="stylesheet" href="/_lib/js/lightbox/css/lightbox.css" type="text/css" media="screen" />
<script src="/_lib/js/lightbox/js/prototype.js" type="text/javascript"></script>
<script src="/_lib/js/lightbox/js/scriptaculous.js?load=effects,builder" type="text/javascript"></script>
<script src="/_lib/js/lightbox/js/lightbox.js" type="text/javascript"></script>


<script type="text/javascript">
sms_templates=<?=json_encode($sms_templates); ?>

function setMaxLength() {
	var x = document.getElementsByTagName('textarea');
	var counter = document.createElement('div');
	counter.className = 'counter';
	for (var i=0;i<x.length;i++) {
		if (x[i].getAttribute('maxlength')) {
			var counterClone = counter.cloneNode(true);
			counterClone.relatedElement = x[i];
			counterClone.innerHTML = '<span>0</span>/'+x[i].getAttribute('maxlength');
			x[i].parentNode.insertBefore(counterClone,x[i].nextSibling);
			x[i].relatedElement = counterClone.getElementsByTagName('span')[0];

			x[i].onkeyup = x[i].onchange = checkMaxLength;
			x[i].onkeyup();
		}
	}
}

function checkMaxLength() {
	var maxLength = this.getAttribute('maxlength');
	var currentLength = this.value.length;
	if (currentLength > maxLength)
		this.relatedElement.className = 'toomuch';
	else
		this.relatedElement.className = '';
	this.relatedElement.firstChild.nodeValue = currentLength;
	// not innerHTML
}

function countSelectedUsers()
{
	var count=0;

	var el, i = 0;
	while (el = $('form').elements[i++]){
		if( el.type == 'checkbox' ){
			if( el.checked ){
				count++;
			}
		}
	}

	$('total_users').innerHTML=count;
}

function update_sms_template()
{
	if( $F('sms_template') ){
		$('sms_message').value=sms_templates[$F('sms_template')]['message'];
	}
}

function init()
{
	/*
	var el, i = 0;
	while (el = $('form').elements[i++]){
		if( el.type == 'checkbox' ){
			new Form.Element.EventObserver(el, countSelectedUsers.bindAsEventListener(el));
		}
	}*/

	setMaxLength();
	//countSelectedUsers();

	Event.observe($('sms_template'), 'change', update_sms_template);
}

Event.observe(window, 'load', init, false);
</script>

<div id="container">
<h1>Send SMS</h1>

<p>Currently have <strong><?=$credits; ?></strong> credits remaining</p>
<p>About to send <?=count($valid_users); ?> text(s)</p>
<br />

<form method="post" id="form">
<input type="hidden" name="sms" value="1">
Send this message:<br>
<select id="sms_template">
	<option value=""></option>
	<?=html_options($opts['sms_template']); ?>
</select><br />
<textarea id="sms_message" name="sms_message" rows="5" cols="30" maxlength="160"><?=$_POST['sms_message']; ?></textarea><br>
<br>
<h2>Recipients</h2>
<table>
<?php
foreach ($valid_users as $v) {
            ?>
<tr>
	<td><input name="users[]" type="checkbox" value="<?=$v['id']; ?>" checked="checked" /></td>
	<td><?=$v['name']; ?></td>
	<td><?=$v['mobile']; ?></td>
</tr>
<?php
        } ?>
</table><br>
<br>
<br>
<button type="submit">Send</button><br>
</form>

</div>
<?php
    }
?>