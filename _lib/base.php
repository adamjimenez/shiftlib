<?php
//send errors and notifications to
$admin_email = 'adam@shiftcreate.com';

function __autoload($class_name) {
    switch( $class_name ){
        case 'paging':
            require(dirname(__FILE__).'/core/paging.class.php');
        break;
        case 'blog':
            require(dirname(__FILE__).'/core/blog.php');
        break;
        case 'auth':
            require(dirname(__FILE__).'/core/auth.php');
        break;
        case 'cms':
            require(dirname(__FILE__).'/cms/cms.php');
        break;
        case 'shop':
            require(dirname(__FILE__).'/core/shop.php');
        break;
        case 'Rmail':
            require(dirname(__FILE__).'/modules/rmail/Rmail.php');
        break;
    }
}

$root_folders = array('httpdocs','htdocs','public_html');

$dir = dirname($_SERVER['SCRIPT_FILENAME']);

//find out root dir
foreach( $root_folders as $root_folder ){
	$pos = strrpos($dir,'/'.$root_folder);

	if( $pos){
		break;
	}
}

if( $pos ){
	$root_folder = substr($dir,0,$pos).'/'.$root_folder;
	chdir($root_folder);
}else{
	die('no '.$root_folder.' folder');
}

require(dirname(__FILE__).'/core/common.php');
include($root_folder.'/_inc/config.php');

if( $db_config['user'] or $db_connection ){
	$cms=new cms;

	if( !$db_connection ){
		$db_connection=mysql_connect($db_config['host'],$db_config['user'],$db_config['pass']) or trigger_error("SQL", E_USER_ERROR);
		mysql_select_db($db_config['name']) or trigger_error("SQL", E_USER_ERROR);
	}

	if( $auth_config ){
        $auth = new auth();
	}
}

include($root_folder.'/_inc/custom.php');

if( $shop_enabled ){
    $shop = new shop();
}
?>