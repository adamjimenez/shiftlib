<?
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/db.php');

$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." WHERE 
	name='".addslashes($_GET['f'])."'
");
$row=mysql_fetch_array($select);

header('Content-type: '.$row['type']);
print $row['data'];
?>