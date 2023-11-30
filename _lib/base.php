<?php
//send errors and notifications to
$admin_email = $_SERVER['SERVER_ADMIN'] ?? null;
$root_folder = $_SERVER['DOCUMENT_ROOT'] ?? null;
$die_quietly = false;
chdir($root_folder);

if (file_exists('vendor/autoload.php')) {
    include('vendor/autoload.php');
}
require(__DIR__ . '/autoload.php');
require_once(__DIR__ . '/core/common.php');
require($root_folder . '/_inc/config.php');

if (!$db_config['user'] && $_SERVER['DB_HOST']) {
    $db_config = [
        'host' => $_SERVER['DB_HOST'],
        'user' => $_SERVER['DB_USER'],
        'pass' => $_SERVER['DB_PASS'],
        'name' => $_SERVER['DB_NAME'],
    ];
}

if ($db_config) {
    $db_connection = mysqli_connect($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']) or trigger_error(mysqli_connect_error(), E_USER_ERROR);
    mysqli_set_charset($db_connection, 'utf8mb4');
}

if ($db_connection) {
    $cms = new cms;
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