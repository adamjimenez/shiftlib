<?
require_once(dirname(__FILE__).'/../base.php');

$auth->check_login();

$select=mysql_query("SELECT * FROM files WHERE 
	id='".addslashes($_GET['f'])."'
");
$row=mysql_fetch_array($select);

if( $vars['files']['dir'] ){
	$row['data']=file_get_contents($vars['files']['dir'].$row['id']);
}

header ("Content-Disposition: attachment; filename=\"" . $row['name'] . "\"");
header('Content-type: '.$row['type']);
print $row['data'];
?>