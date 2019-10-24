<?php
require('../base.php');

$auth->check_admin();
//debug($_GET, 1); exit;

$response = array();

if ($_GET['cmd']=='reorder') {
	foreach($_POST['items'] as $v) {
		sql_query("UPDATE ".escape(underscored($_GET['section']))." SET
			`position` = '".escape($v['position'])."'
			WHERE
				id = '".escape($v['id'])."'
		");
	}
	
	$response['success'] = true;
} else {

	$sql = $cms->conditions_to_sql($_GET['section'], $_GET);
	
	$labels = array();
	
	foreach($vars['labels'][$_GET['section']] as $v) {
		if($v !== 'id`') {
			$labels[] = $v;
		}
	}
	
	if (!count($labels)) {
		$labels = array(key($vars['fields'][$_GET['section']]));
	}
	
	array_unshift($labels, 'id');
	array_unshift($labels, 'id');
	
	if (in_array('position', $vars['fields'][$_GET['section']])) {
		$order = 'position';
	} else {
		$order = underscored($labels[($_GET['order'][0]['column']-1)]) ?: 'id';
	}

	$dir = ($_GET['order'][0]['dir']=='desc') ? 'DESC' : '';
	
	$count = sql_query("SELECT count(*) AS `count` FROM ".escape(underscored($_GET['section']))." T_".escape(underscored($_GET['section']))."
	".$sql['joins']."
	".$sql['where_str']."
	", 1);
	
	$response = array(
		'draw' => $_GET['draw'],
		'data' => array(),
		'recordsTotal' => $count['count'],
		'recordsFiltered' => $count['count'],
	);
	
	$start = $_GET['start'];
	$length = $_GET['length'];
	$limit = '';
	
	if ($length!=-1) {
		$limit = "LIMIT ".(int)$start.", ".(int)$length;
	}
	
	$table = escape(underscored($_GET['section']));
	
	$rows = sql_query("SELECT T_$table.* ".$sql['cols']." FROM $table T_$table
	".$sql['joins']."
	".$sql['where_str']."
	ORDER BY
	T_$table.$order $dir
	$limit
	");
	
	//debug($sql);
	
	foreach($rows as $v) {
		$item = array();
		
		$item[] = $v['position'];
		
		foreach( $labels as $i=>$k ){
			if($v[underscored($k).'_label']) {
				$item[] = $v[underscored($k).'_label'];
			} else {
				$item[] = $v[underscored($k)];
			}
		}
		
		$response['data'][] = $item;
	}
}

print json_encode($response);