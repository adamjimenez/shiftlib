<?
require(dirname(__FILE__).'/../base.php');

$engines = array(
	'google'=>"http://www.google.com/webmasters/tools/ping?sitemap=",
	'yahoo'=>"http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=",
	'ask'=>"http://submissions.ask.com/ping?sitemap=",
	'msn'=>"http://webmaster.live.com/ping.aspx?siteMap="
);	

if( $_POST['engines'] ){
	$host=$_SERVER['HTTP_HOST'];
	
	$sitemap = 'http://'.$host.'/_lib/seo/sitemap.xml.php';
	
	foreach( $_POST['engines'] as $k=>$v ){
		if( $engines[$k] ){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $engines[$k].$sitemap);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch);
			curl_close($ch);
			
			print 'sent to '.$k.'<br>';
		}
	}
}
?>

<h1>SEO tools</h1>

<p><a href="/robots.txt">robots.txt</a></p>
<p><a href="/sitemap.xml">sitemap.xml</a></p>
<p><a href="/plesk-stat/webstat">web stats</a></p>
<p>
	<h2>Submit sitemaps</h2>
	<form method="post">
	<? foreach( $engines as $k=>$v ){ ?>
		<label><input type="checkbox" name="engines[<?=$k;?>]" value="1" checked> <?=$k;?></label><br>
	<? } ?>
	<p><button type="submit">Do it</button></p>
	</form>
</p>