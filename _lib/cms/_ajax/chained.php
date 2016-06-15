<?
require('../../base.php');

// fixme for frontend field
$auth->check_login();

$section = $_GET['section'];
$value = (int)$_GET['value'];

if (!isset($section) or !isset($value)) {
	die('missing fields');
}

// get field
$field = '';
foreach( $vars['fields'][$section] as $k=>$v ){
	if( $v!='separator' ){
		$field = $k;
		break;
	}
}

if (!$field) {
	die('missing field');
}

$data = array();
$options = array();

function get_items($value) {
	global $options, $section, $field, $last_parent;
	
	$item = sql_query("SELECT parent FROM `".escape($section)."`
		WHERE 
			id = '".escape($value)."'
	", 1);

	$parent = $item['parent'];
	
	$parents = sql_query("SELECT id, ".$field." FROM `".escape($section)."`
		WHERE 
			parent = '".escape($parent)."'
	");

	$opts = array();
	foreach($parents as $v) {
		$opts[$v['id']] = array('value' => $v[$field]);
	}
	
	if ($opts[$value]) {
		$opts[$value]['children'] = $options;
	}
	
	$options = $opts;
	
	if ($parent!=0) {
		get_items($parent);
	}
}

if ($_GET['parent']) {
	get_items($value);
} else {
	$parents = sql_query("SELECT id, ".$field." FROM `".escape($section)."`
		WHERE 
			parent = '".escape($value)."'
	");
	
	foreach($parents as $v) {
		$options[$v['id']] = array('value' => $v[$field]);
	}
}

$data['options'] = $options;
header('Content-Type: application/json, charset=utf-8');
print json_encode($data);
?>