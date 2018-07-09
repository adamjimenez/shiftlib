<?php
/*
File:		default.php
Author:		Adam Jimenez
*/

require_once(dirname(__FILE__).'/core/common.php');

function add_components($html){
	return preg_replace_callback('/{\$([A-Za-z0-9]+)}/', function($match) {
		$include = $match[1];
		return file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/components/'.$include);
	}, $html);
}

function get_tpl_catcher($request)
{
	global $tpl_config;

	if( !$tpl_config or !is_array($tpl_config['catchers']) ){
		return false;
	}else{
		foreach( $tpl_config['catchers'] as $catcher ){
			if( substr($request, 0, strlen($catcher)+1)==$catcher.'/' ){
				return $catcher;
			}
		}
	}
	return false;
}

function timer()
{
	global $auth;

	if( !$auth->user['admin'] ){
		return;
	}

	global $timer_start;

	if( !$timer_start ){
		$timer_start=microtime(true);
		$timer_now=$timer_start;
	}else{
		$timer_now=microtime(true);
	}

	$diff=$timer_now-$timer_start;

	$timer_start=$timer_now;

	print '<p>'.round($diff,4).'</p>';
}

function trigger_404(){
	throw new Exception(404);
}

function stop(){
	throw new Exception('stop');
}

function parse_request(){
	global $tpl_config;

	$script_url = rawurldecode($_SERVER['REQUEST_URI']);
	$pos = strpos($script_url, '?');

	if($pos) {
		$script_url = substr($script_url, 0, $pos);
	}

	if( substr($script_url,-6)=='/index' ){ //redirect /index to /
		redirect(substr($script_url,0,-5),true);
	}

	if( $script_url=='index' ){
		redirect('/');
	}

	$request = $script_url ?: 'index';

	if( substr( $request, -1)=='/' ){
		$request.='index';
	}

	//strip prepending slash
	if( substr( $request,0,1)=='/' ){
		$request=(substr($request,1));
	}

	return $request;
}

function get_include( $request ){
	global $tpl_config, $root_folder, $catcher, $sections;

	$include_file = false;

	if( in_array($request, $tpl_config['catchers']) or file_exists($root_folder.'/_tpl/'.$request.'/index.php') ){
		redirect('/'.$request.'/', true);
	}elseif( $tpl_config['redirects']['http://'.$_SERVER['HTTP_HOST'].'/'] ){
		$redirect = $tpl_config['redirects']['http://'.$_SERVER['HTTP_HOST'].'/'];
		redirect($redirect, true);
	}elseif( file_exists($root_folder.'/_tpl/'.$request.'.php') ){
		$include_file = $root_folder.'/_tpl/'.$request.'.php';
		//check redirects
	}elseif( $tpl_config['redirects'][$request] ){
		$redirect = $tpl_config['redirects'][$request];
		redirect($redirect, true);
	}elseif( $catcher=get_tpl_catcher($request) ){
		$include_file = $root_folder.'/_tpl/'.$catcher.'.php';
	}else{
		//check aliases
		if( $tpl_config['alias'][$request] ){
			$include_file=$root_folder.'/_tpl/'.$tpl_config['alias'][$request].'.php';
		}else{
			if( (file_exists('_tpl/'.$request) and !is_dir('_tpl/'.$request)) or $tpl_config['alias'][str_replace('.php','',$request)] ){
				$url='http://'.$_SERVER['SERVER_NAME'].'/'.str_replace('.php','',$request);

				unset($_GET['page']);

				if( count($_GET) ){
					$url.='?'.http_build_query($_GET);
				}

				redirect($url,true);
			}

			//check alias folder
			if( $tpl_config['alias'][$request.'/index'] ){
				redirect("http://".$_SERVER['SERVER_NAME'].'/'.$request.'/',301);
			}

			//check if using urlencode
			$decoded = urldecode($request);
			if( !file_exists($request) and file_exists($decoded) ){
				redirect('/'.$decoded);
			}

			if( file_exists($root_folder.'/_inc/catch_all.php') ){
				$include_file = $root_folder.'/_inc/catch_all.php';
			}else{
				$trigger_404=true;
			}
		}
	}

	return $include_file;
}

//comes before base.php so that custom.php can use request variable
if(!$request){
	$request = parse_request();
}

require(dirname(__FILE__).'/base.php');

//ssl - must come after base.php
if( !$_SERVER['HTTPS'] and ($tpl_config['ssl'] or in_array($request,$tpl_config['secure'])) ){
	if( substr($request,-5)=='index' ){
		$request=substr($request,0,-5);
	}

	if( $_SERVER['QUERY_STRING'] ){
		$request.='?'.$_SERVER['QUERY_STRING'];
	}

	redirect('https://'.$_SERVER['HTTP_HOST'].'/'.$request);
}elseif( $_SERVER['HTTPS'] and (!$tpl_config['ssl'] and !in_array($request,$tpl_config['secure'])) ){
	if( substr($request,-5)=='index' ){
		$request=substr($request,0,-5);
	}

	if( $_SERVER['QUERY_STRING'] ){
		$request.='?'.$_SERVER['QUERY_STRING'];
	}

	redirect('http://'.$_SERVER['HTTP_HOST'].'/'.$request);
}

$time_start = microtime(true);

//check for predefined pages
switch( $request ){
	case 'admin':
		if(!$cms){
			die('Error: db is not configured');
		}

		$cms->admin();
		exit;
	break;
	case 'sitemap.xml':
		require(dirname(__FILE__).'/modules/seo/sitemap.xml.php');
		exit;
	break;
	case 'logout':
		$auth->logout();
		redirect('/');
	break;
}

//current tab
$sections = explode('/', $request);

//templates
$catcher = '';
$include_file = get_include($request);

ob_start("ob_gzhandler");

if( end($sections)=='template' ){
	$trigger_404=true;
}elseif( $include_file===false ){
	$trigger_404=true;
}elseif( $include_file ){
	ob_start();
	try {
		require($include_file);
	} catch (Exception $e) {
		switch( $e->getMessage() ){
			case 404:
				$trigger_404=true;
			break;
			case 'stop':
				$stop=true;
			break;
			default:
				$msg=$e->getMessage()."\n".$e->getTraceAsString();
				$msg=nl2br($msg);

				error_handler(E_USER_ERROR, $msg,$e->getFile(),$e->getLine());
			break;
		}
	}
	$include_content = ob_get_contents();
	ob_end_clean();
}

if( $stop ){
	echo $include_content;
}else{
	if( $trigger_404 ){
		header("HTTP/1.0 404 Not Found");

		if( file_exists($root_folder.'/_tpl/404.php') ){
			ob_start();
			require($root_folder.'/_tpl/404.php');
			$include_content = ob_get_contents();
			ob_end_clean();
		}else{
			$include_content='<h1>404 - Page can not be found</h1>';
		}
	}

	$title = strip_tags($title);

	if( !$title and $title!==false and preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $include_content, $matches) ){
		$title = strip_tags($matches[1]);
		$title = trim(preg_replace( "/\r|\n/", "", $title));
	}

	header("Access-Control-Allow-Origin: *");

	if( $use_template===false ){
		echo $include_content;
	}elseif( file_exists($root_folder.'/_tpl/'.dirname($request).'/template.php') ){
		require($root_folder.'/_tpl/'.dirname($request).'/template.php');
	}elseif( $catcher and file_exists($root_folder.'/_tpl/'.dirname($catcher).'/template.php') ){
		require($root_folder.'/_tpl/'.dirname($catcher).'/template.php');
	}else{
		require($root_folder.'/_tpl/template.php');
	}

	$time_end = microtime(true);
	$time = $time_end - $time_start;

	if( $auth->user['admin'] and $_GET['time'] ){
		echo '<span style="color:yellow; background: red; position:absolute; top:0; left:0;">Loaded in '.number_format($time,3).' seconds</span>';
	}
}

//log slow pages
/*
if( $time>1 ){
$body='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?'.$_SERVER['QUERY_STRING']."\n";
$body.='time: '.$time;

mail($admin_email,'Slow page', $body, $headers );
}
*/
?>