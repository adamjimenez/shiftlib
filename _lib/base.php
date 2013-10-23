<?php
//send errors and notifications to
$admin_email = 'adam@shiftcreate.com';

function __autoload($class) {
    switch( $class ){
        case 'paging':
            require(dirname(__FILE__).'/core/paging.class.php');
            return;
        break;
        case 'blog':
            require(dirname(__FILE__).'/core/blog.php');
            return;
        break;
        case 'auth':
            require(dirname(__FILE__).'/core/auth.php');
            return;
        break;
        case 'cms':
            require(dirname(__FILE__).'/cms/cms.php');
            return;
        break;
        case 'shop':
            require(dirname(__FILE__).'/core/shop.php');
            return;
        break;
        case 'Rmail':
            require(dirname(__FILE__).'/modules/rmail/Rmail.php');
            return;
        break;
        case 'txtlocal':
            require(dirname(__FILE__).'/modules/sms/sms.php');
            require(dirname(__FILE__).'/modules/sms/txtlocal.php');
            return;
        break;
    }

    //bit hacky
    if( function_exists('DOMPDF_autoload') ){
        DOMPDF_autoload($class);
        return;
    }

    //trigger_error('no such class: '.$class, E_USER_ERROR);
}

$root_folders = array('httpdocs', 'htdocs', 'public_html', 'html');

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
	die('no '.$root_folder.' folder: '.$dir);
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