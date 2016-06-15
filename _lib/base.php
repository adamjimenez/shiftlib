<?php
//send errors and notifications to
//$admin_email = 'adam@shiftcreate.com';

$admin_email = $_SERVER['SERVER_ADMIN'];

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
        case 'Hybrid_Auth':
            require(dirname(__FILE__).'/modules/hybridauth/Hybrid/Auth.php');
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


//symlinked dir?
$root_folder = $_SERVER['DOCUMENT_ROOT'];
chdir($root_folder);

require_once(dirname(__FILE__).'/core/common.php');
include($root_folder.'/_inc/config.php');

if( $db_config['user'] or $db_connection ){
	$cms = new cms;

	if( !$db_connection ){
		$db_connection = mysqli_connect($db_config['host'],$db_config['user'],$db_config['pass'],$db_config['name']) or trigger_error("SQL", E_USER_ERROR);
		mysqli_set_charset($db_connection, 'utf8');;
	}

    $auth = new auth();
}elseif( $db_config!==false ){
    //prompt to configure connection
    require(dirname(__FILE__).'/cms/_tpl/db.php');
    exit;
}

include($root_folder.'/_inc/custom.php');

if( $shop_enabled ){
    $shop = new shop();
}
?>