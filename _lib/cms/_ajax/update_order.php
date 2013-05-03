<?
require(dirname(__FILE__).'/../../base.php');

$auth->check_admin();

$table=str_replace(' ','_',$_POST['section']);

if (!isset($_POST['items_'.$table]) || !is_array($_POST['items_'.$table])){
	die('no items');
};

if( $_POST['where'] ){
	foreach( $where as $k=>$v ){
		$where_str.="`".escape($k)."`='".escape($k)."'"."\n";
	}
}

$query="SELECT id FROM `".addslashes($table)."`"."\n";

if( $where_str ){
	$query.="WHERE $where_str"."\n";
}

$query.="ORDER BY position";
	
$result = mysql_query($query) or trigger_error("SQL", E_USER_ERROR);

$items = array();
while ($row = mysql_fetch_array($result)) {
	$items[] = $row['id'];
}

$ranking = 1;
foreach ($_POST['items_'.$table] as $item_id) {
	print $item_id;
	if( !in_array($item_id, $items) ){
		continue;
	}	

	print $ranking;
	print '<br>';
	
	mysql_query("UPDATE `".escape($table)."` SET 
		position = '$ranking' 
		WHERE 
			id = '$item_id' 
		LIMIT 1
	") or trigger_error("SQL", E_USER_ERROR);
	
	if( in_array('language',$vars['fields'][$_POST['section']]) ){
		mysql_query("UPDATE `".escape($table)."` SET 
			position = '$ranking' 
			WHERE 
				translated_from = '$item_id' 
			LIMIT 1
		") or trigger_error("SQL", E_USER_ERROR);
	}
	
	$ranking++;
}
?>