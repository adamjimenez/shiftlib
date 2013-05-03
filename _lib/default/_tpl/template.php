<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Untitled | <?=$title ? $title : 'Main'; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="Description" content="<?=$meta_description ? $meta_description : ''; ?>" />
	<link rel="stylesheet" type="text/css" href="/_lib/css/reset.css">
	<? load_js(array('prototype')); ?>
	<script type="text/javascript">
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-7875761-2']);
	  _gaq.push(['_trackPageview']);
	
	  (function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	</script>
</head>
<body>
	<div id="header" style="margin:0 auto; width:800px;">
		<div style="float:left">
			<a href="/" id="logo"><img src="/images/logo.gif" alt="logo" vspace="5" hspace="5" /></a>
		</div>
		<div style="text-align:right;">
			<? if( $auth->user ){ ?>
			<?=$auth->user['email'];?>
			| <a href="/account">Account</a> | <a href="/logout">Sign out</a>
			<? }else{ ?>
			<a href="/login">Sign in</a>
			<? } ?>
		</div>
		<div id="nav" style="float:left;">
			<ul>
				<li<? current_tab('index'); ?>><a href="/" title="Home">Home</a></li>
			</ul>
		</div>
	</div>
	<div id="content" style="margin:0 auto; width:800px;">
		<?=$include_content; ?>
		<br clear="all" />
	</div>
	<div id="footer" style="margin:0 auto; width:800px;">
		<ul>
			<li><a href="/privacy">Privacy Policy</a></li>
			<li><a href="/terms">Terms &amp; Conditions</a></li>
			<li>&copy; <?=date('Y');?></li>
		</ul>
	</div>
</body>
</html>