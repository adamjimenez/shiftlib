<?
if( $auth->user['email']=='admin' and $auth->user['password']=='123' ){
	$_SESSION["warning"] = 'Warning: you are using the default password. <a href="?option=preferences">Change it</a>';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Admin Area | <?=$title ? $title : 'Main'; ?></title>

	<style>
	*{
		margin:0;
		padding:0;
	}
	</style>

	<link href="/_lib/cms/css/cms.css" rel="stylesheet" type="text/css">

	<? if($admin_config['theme']){ ?>
	<link href="/_lib/cms/css/themes/<?=$admin_config['theme'];?>.css" rel="stylesheet" type="text/css">
	<? } ?>

	<script type="text/javascript">
	var section='<?=$vars['section'];?>';
	var fields=(<?=json_encode($fields);?>);
	</script>

	<? load_js(array('jqueryui', 'cms', 'google', 'lightbox', 'fontawesome')); ?>
	<script type="text/javascript" src="/_lib/cms/js/list.js"></script>

	<? //to be used for custom js ?>
	<? if( file_exists('_inc/js/cms.js') ){ ?>
	<script type="text/javascript" src="/_inc/js/cms.js"></script>
	<? } ?>

	<!--[if IE]>
	<style>
	fieldset {
		position: relative;
	}
	legend {
		position: absolute;
		top: -.5em;
		left: .5em;
	}
	</style>
	<![endif]-->
</head>
<body>
	<table width="100%" height="100%">
	<tr>
		<td height="50">
			<table align="center" width="100%" id="header">
			<tr>
				<td width="230" valign="middle">
						<? if( file_exists('images/logo.gif') ){ ?>
						<a href="/admin">
							<img src="/images/logo.gif" alt="Admin home" align="middle" style="padding:0 10px; max-height:50px;">
						</a>
						<?
						}else{
							$website=ucfirst(str_replace('www.','',$_SERVER['HTTP_HOST']));
							$arr=explode('.',$website);

							$website=$arr[0];
						?>
						<a href="/admin" style="font-size:24px; color:#000000; padding:0 17px; text-decoration:none;">
							<?=$website;?>
						</a>
						<? } ?>
				</td>
				<td style="text-align:center; vertical-align:bottom;">
					<?
					if($_SESSION['message']){
					?>
					<div id="cms_message"><?=$_SESSION['message'];?></div>
					<?
						unset($_SESSION['message']);
					}
					?>
					<?
					if($_SESSION['warning']){
					?>
					<div id="cms_warning"><?=$_SESSION['warning'];?></div>
					<?
						unset($_SESSION['warning']);
					}
					?>
				</td>
				<td align="right" valign="top" style="text-align:right;" width="200">
					<? if( $auth->user['admin'] ){ ?>
						<?=$auth->user['name'] ? $auth->user['name'].' '.$auth->user['surname'] : $auth->user['email'];?> |
						<a href="../">Website</a> |
						<a href="?option=logout">Log out</a>
					<? }else{ ?>
					<a href="?option=login">Log in</a>
					<? } ?>
				</td>
			</tr>
			</table>
		</td>
	</tr>

	<tr>
		<td>
			<table align="center" width="100%" height="100%" cellpadding="0" cellspacing="0">
			<tr>
				<td valign="top" width="120" id="menu">
					<?
					if( is_array($vars['sections']) and $auth->user['admin'] ){
					?>
					<ul>
						<?
							foreach( $vars['sections'] as $section){
								if( $section=='-' ){
						?>
								<hr>
						<?
								}elseif( $auth->user['admin']==1 or $auth->user['privileges'][$section] ){
						?>
							<li <? if($section==$_GET['option']){ ?>id="current"<? } ?>><a href="?option=<?=$section;?>" title="<?=ucfirst($section);?>"><?=ucfirst($section);?></a></li>
						<?
								}
							}
						?>
					</ul>

					<br>

					<ul>
						<? if( $shop_enabled and ($auth->user['admin']==1 or $auth->user['privileges']['orders']) ){ ?>
						<li <? if($_GET['option']=='orders'){ ?>id="current"<? } ?>><a href="?option=orders">Orders</a></li>
						<? } ?>
						<? if( ($auth->user['admin']==1 or $auth->user['privileges']['email_templates']) ){ ?>
						<li <? if($_GET['option']=='email_templates'){ ?>id="current"<? } ?>><a href="?option=email templates">Email Templates</a></li>
						<? } ?>
						<? if( $auth->user['admin']==1 and $sms_config['provider'] ){ ?>
						<li <? if($_GET['option']=='sms templates'){ ?>id="current"<? } ?>><a href="?option=sms templates">SMS Templates</a></li>
						<? } ?>

    					<? if( $auth->user['admin']==1 or $auth->user['privileges']['uploads'] ){ ?>
						<li><a href="#" class="upload">Uploads</a></li>
						<? } ?>

						<? if( $vars['configure_dropdowns'] and ($auth->user['admin']==1 or $auth->user['privileges']['email_templates']) ){ ?>
						<li <? if($_GET['option']=='dropdowns'){ ?>id="current"<? } ?>><a href="?option=dropdowns">Dropdowns</a></li>
						<? } ?>

						<? if( $auth->user['admin'] ){ ?>
						<li <? if($_GET['option']=='preferences'){ ?>id="current"<? } ?>><a href="?option=preferences">Preferences</a></li>
						<? } ?>

						<? if( $auth->user['admin']==1 ){ ?>
						<li <? if($_GET['option']=='configure'){ ?>id="current"<? } ?>><a href="?option=configure">Configure</a></li>
						<? } ?>
					</ul>
					<?
					}
					?>
				</td>
				<td id="content" valign="top" style="height:100%;">
					<div class="inner" style="height:100%;"><?=$include_content;?></div>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
</body>
</html>