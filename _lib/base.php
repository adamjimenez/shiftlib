<?php
//send errors and notifications to
//$admin_email = 'adam@shiftcreate.com';

$admin_email = $_SERVER['SERVER_ADMIN'];

require(dirname(__FILE__) . '/autoload.php');

//symlinked dir?
$root_folder = $_SERVER['DOCUMENT_ROOT'];
chdir($root_folder);

require_once(dirname(__FILE__) . '/core/common.php');
require($root_folder . '/_inc/config.php');

if ($db_config['user'] or $db_connection) {
    $cms = new cms;

    if (!$db_connection) {
        if ('stage' == $_SERVER['LIB_ENV']) {
            $db_connection = mysqli_connect($db_config['dev_host'], $db_config['dev_user'], $db_config['dev_pass'], $db_config['dev_name']) or trigger_error(mysqli_connect_error(), E_USER_ERROR);
            mysqli_set_charset($db_connection, 'utf8');
        } else {
            $db_connection = mysqli_connect($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']) or trigger_error(mysqli_connect_error(), E_USER_ERROR);
            mysqli_set_charset($db_connection, 'utf8');
        }
    }

    $auth = new auth();
    $auth->init();
} elseif (false !== $db_config) {
    //prompt to configure connection
    require(dirname(__FILE__) . '/cms/_tpl/db.php');
    exit;
}

include($root_folder . '/_inc/custom.php');

if ($shop_enabled) {
    $shop = new shop();
}
