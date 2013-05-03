<?php
require(dirname(__FILE__).'/../../../_lib/base.php');

$auth->check_login();

if( $auth->user and $upload_config['user_uploads'] ){
	$upload_config['user']=$auth->user['id'];
}elseif( $auth->user['admin']!=1 and $auth->user['privileges']['uploads']!=2 ){
	die('Permission denied.');
}

/*
if( $_GET['dir'] ){
	$upload_config['upload_dir']=$_GET['dir'];
}*/

require(dirname(__FILE__).'/phpupload.class.php');

$upload_config['type']=$upload_config['type'] ? $upload_config['type'] : 'dir';

if( $upload_config['type']=='db' ){
	require_once( dirname(__FILE__).'/phpupload_db.class.php' );

	$upload=new phpupload_db;
}else{
	require_once( dirname(__FILE__).'/phpupload_dir.class.php' );

	$upload=new phpupload_dir;
}

$upload->browse();
?>