<?php
require_once(dirname(__FILE__) . '/../base.php');

$auth->check_login();

if (1 != $auth->user['admin'] and !$auth->user['privileges']['uploads']) {
    die('access denied');
}

$row = sql_query("SELECT * FROM files WHERE 
	id='" . addslashes($_GET['f']) . "'
", 1);

if ($vars['files']['dir']) {
    $row['data'] = file_get_contents($vars['files']['dir'] . $row['id']);
}

header('filename="' . $row['name'] . '"');
header('Content-type: ' . $row['type']);
print $row['data'];
