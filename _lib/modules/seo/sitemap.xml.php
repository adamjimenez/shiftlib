<?
$host=$_SERVER['HTTP_HOST'];

$files = array();

function get_dir_contents( $dirstr='_tpl/' ) {
	global $files;

	if( is_array( glob($dirstr.'*') ) ){
		foreach (glob($dirstr.'*') as $pathname) {
			$filename=basename($pathname);

			if(
				$filename=='admin'
				or $filename=='admin.php'
				or $filename=='template.php'
				or $filename=='404.php'
				or strstr($pathname,'old')
				or strstr($pathname,'includes')
			){
				continue;
			}

			if($filename!='.' AND $filename!='..' AND $pathname!=$dirstr){
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

header('Content-Type: text/xml');
print '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">

   <url>
      <loc>http://'.$host.'/</loc>
      <changefreq>weekly</changefreq>
      <priority>0.8</priority>
   </url>
';

//scan template folder
foreach( $files as $file ){
	//check for login
	$content=file_get_contents($file);

	if( !$auth_config['check_all'] and strstr($content,'$auth->check_login()') ){
		continue;
	}elseif( $auth_config['check_all'] and $file!=$auth->login ){
		continue;
	}

	$mtime=filemtime($file);
	$date=date('Y-m-d',$mtime);

	//clean up
	$file=substr($file,5,-4);

	print '   <url>
		  <loc>http://'.$host.'/'.$file.'</loc>
		  <lastmod>'.$date.'</lastmod>
	   </url>
';

	if( get_tpl_catcher($file.'/') ){
		if( class_exists('catchers') ){
			$catchers=new catchers;

			if( method_exists($catchers,$file) ){

				$pages=$catchers->$file();

				foreach($pages as $page){
	print '   <url>
		  <loc>http://'.$host.'/'.$file.'/'.$page.'</loc>
	   </url>
';
				}
			}
		}
	}
}

print '</urlset>';