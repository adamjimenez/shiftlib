<?php
$files = array();
function get_dir_contents( $dirstr='_tpl/' ) {
	global $files;

	if( is_array( glob($dirstr.'*') ) ){
		foreach (glob($dirstr.'*') as $pathname) {
			$filename = basename($pathname);

			if(
				$filename=='admin'
				or $filename=='admin.php'
				or $filename=='template.php'
				or $filename=='404.php'
				or strstr($pathname,'dev')
				or strstr($pathname,'old')
				or strstr($pathname,'includes')
			){
				continue;
			}

			if($filename!='.' and $filename!='..' and $pathname!=$dirstr){
				if( is_dir($pathname) ){
					get_dir_contents($pathname.'/');
				}else{
					array_push($files, $pathname);
				}
			}
		}
	}

	 return $files;
}

get_dir_contents();

$pages = array();
foreach( $files as $file ){
	$mtime = filemtime($file);
	$date = date('Y-m-d',$mtime);

	//remove extension
	if( ends_with($file, '.php') ){
	    $file = substr($file, 0, -4);
	}

	//remove _tpl
	if( starts_with($file, '_tpl/') ){
	    $file = substr($file, 5);
	}

    if( in_array($file, $tpl_config['catchers']) ){
        continue;
    }

	if( ends_with($file, 'index') ){
	    $file = substr($file, 0, -5);
	}

	if( $file ){
	    $pages[] = $file;
	}
}

if( function_exists('get_pagenames') ){
    $pages = array_merge($pages, get_pagenames());
}

if( $sitemap_ignore ){
    $pages = array_diff($pages, $sitemap_ignore);
}
?>