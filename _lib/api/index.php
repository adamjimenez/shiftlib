<?php
require('../base.php');

$auth->check_admin();
//debug($_GET, 1); exit;

$response = [];

if ('reorder' == $_GET['cmd']) {
    foreach ($_POST['items'] as $v) {
        sql_query('UPDATE ' . escape(underscored($_GET['section'])) . " SET
			`position` = '" . escape($v['position']) . "'
			WHERE
				id = '" . escape($v['id']) . "'
		");
    }
    
    $response['success'] = true;
} else {
    $sql = $cms->conditions_to_sql($_GET['section'], $_GET);
    
    $labels = [];
    
    foreach ($vars['labels'][$_GET['section']] as $v) {
        if ('id`' !== $v) {
            $labels[] = $v;
        }
    }
    
    if (!count($labels)) {
        $labels = [key($vars['fields'][$_GET['section']])];
    }
    
    array_unshift($labels, 'id');
    array_unshift($labels, 'id');
    
    if (in_array('position', $vars['fields'][$_GET['section']])) {
        $order = 'position';
    } else {
        $order = underscored($labels[($_GET['order'][0]['column'] - 1)]) ?: 'id';
    }

    $dir = ('desc' == $_GET['order'][0]['dir']) ? 'DESC' : '';
    
    $count = sql_query('SELECT count(*) AS `count` FROM ' . escape(underscored($_GET['section'])) . ' T_' . escape(underscored($_GET['section'])) . '
	' . $sql['joins'] . '
	' . $sql['where_str'] . '
	', 1);
    
    $response = [
        'draw' => $_GET['draw'],
        'data' => [],
        'recordsTotal' => $count['count'],
        'recordsFiltered' => $count['count'],
    ];
    
    $start = $_GET['start'];
    $length = $_GET['length'];
    $limit = '';
    
    if (-1 != $length) {
        $limit = 'LIMIT ' . (int) $start . ', ' . (int) $length;
    }
    
    $table = escape(underscored($_GET['section']));
    
    $rows = sql_query("SELECT T_$table.* " . $sql['cols'] . " FROM $table T_$table
	" . $sql['joins'] . '
	' . $sql['where_str'] . "
	ORDER BY
	T_$table.$order $dir
	$limit
	");
    
    //debug($sql);
    
    foreach ($rows as $v) {
        $item = [];
        
        $item[] = $v['position'];
        
        foreach ($labels as $i => $k) {
            if ($v[underscored($k) . '_label']) {
                $item[] = $v[underscored($k) . '_label'];
            } else {
                $item[] = $v[underscored($k)];
            }
        }
        
        $response['data'][] = $item;
    }
}

print json_encode($response);
