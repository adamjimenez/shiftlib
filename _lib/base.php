<?php
//send errors and notifications to
$admin_email = $_SERVER['SERVER_ADMIN'];
$root_folder = $_SERVER['DOCUMENT_ROOT'];
chdir($root_folder);

include('vendor/autoload.php');
require(__DIR__ . '/autoload.php');
require_once(__DIR__ . '/core/common.php');
require($root_folder . '/_inc/config.php');

if ($db_config['user'] or $db_connection) {
    $cms = new cms;

    if (!$db_connection) {
        
        $db_host = $db_config['host'];
        $db_user = $db_config['user'];
        $db_pass = $db_config['pass'];
        $db_name = $db_config['name'];
        
        if ($_SERVER['DB_HOST']) {
            
            $db_host = $_SERVER['DB_HOST'];
            $db_user = $_SERVER['DB_USER'];
            $db_pass = $_SERVER['DB_PASS'];
            $db_name = $_SERVER['DB_NAME'];
            
        } else if ('stage' == $_SERVER['LIB_ENV']) {
            
            $db_host = $db_config['dev_host'];
            $db_user = $db_config['dev_user'];
            $db_pass = $db_config['dev_pass'];
            $db_name = $db_config['dev_name'];
            
        }
        
        $db_connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name) or trigger_error(mysqli_connect_error(), E_USER_ERROR);
        mysqli_set_charset($db_connection, 'utf8mb4');
        
    }

    $auth = new auth();
} elseif (false !== $db_config) {
    //prompt to configure connection
    require(__DIR__ . '/cms/_tpl/db.php');
    exit;
}

include($root_folder . '/_inc/custom.php');

if ($auth) {
    $auth->init($auth_config);
}

//backcompat
if ($cms_buttons) {
    $cms->addButton($cms_buttons);
}
if ($cms_handlers) {
    $cms->bind($cms_handlers);
}

if ($shop_enabled) {
    $shop = new shop();
}
