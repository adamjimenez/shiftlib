<?
require('seo.php');

$host = $_SERVER['HTTP_HOST'];

$protocol = $_SERVER['HTTPS'] ? 'https' : 'http';

//print_r($pages); exit;

header('Content-Type: text/xml');
print '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">

   <url>
      <loc>'.$protocol.'://'.$host.'/</loc>
      <changefreq>weekly</changefreq>
      <priority>0.8</priority>
   </url>
';

//scan template folder
foreach( $pages as $page ){
	print '   <url>
      <loc>'.$protocol.'://'.$host.'/'.$page.'</loc>
   </url>
';
}

print '</urlset>';