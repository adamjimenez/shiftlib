<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

require('../../base.php');

$name = $_GET['field'];

if( !isset($vars['options'][$name]) ){
	die('no options');
}

//$options=$cms->get_options($_GET['field']);

$table = underscored($vars['options'][$name]);

foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
	if( $v!='separator' ){
		$field = $k;
		break;
	}
}

$raw_option = $vars['fields'][$vars['options'][$name]][$field];

$cols='';
if( is_array($raw_option) ){
	$db_field_name = $this->db_field_name($vars['options'][$name], $field);
	$cols .= "".underscored($db_field_name)." AS `".underscored($field)."`"."\n";
}else{
	$cols .= '`'.underscored($field).'`';
}

if( in_array('language', $vars['fields'][$vars['options'][$name]]) ){
	$language = $this->language ? $this->language : 'en';

	$rows = sql_query("SELECT id,$cols FROM
		$table
		WHERE
			language='".$language."'
		ORDER BY `".underscored($field)."`
	") or trigger_error("SQL", E_USER_ERROR);

	$options=array();
	foreach($rows as $row){
		if( $row['translated_from'] ){
			$id = $row['translated_from'];
		}else{
			$id = $row['id'];
		}

		$options[$id] = $row[underscored($field)];
	}
}else{
	$parent_field = array_search('parent', $vars['fields'][$vars['options'][$name]]);

	if( $parent_field!==false ){
		$options = $this->get_children($vars['options'][$name], $parent_field);
	}else{
		$rows = sql_query("SELECT id,$cols FROM
			$table
			WHERE
				`$field` LIKE '".escape($_GET['term'])."%'
			ORDER BY `".underscored($field)."`
			LIMIT 10
		");

		$options = array();
		foreach($rows as $row){
			$options[$row['id']] = $row[underscored($field)];
		}
	}
}

$results = array();
foreach( $options as $k=>$v ){
	$results[]=array(
		'value'=>$k,
		'label'=>$v
	);
}

print json_encode($results);