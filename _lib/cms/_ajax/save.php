<?php
require(dirname(__FILE__).'/../../base.php');

$auth->check_admin();

$data = json_decode($_POST['data'], true);

foreach( $data as $v ){
    $table = underscored($v['section']);

    sql_query("UPDATE `".escape($table)."` SET
        `".escape($v['field'])."` = '".escape($v['value'])."'
        WHERE
            id = '".escape($v['id'])."'
        LIMIT 1
    ");
}

$result = array('success'=>true);

print json_encode($result);
?>