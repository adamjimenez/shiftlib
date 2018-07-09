<?
if( $auth->user['email']=='admin' and $auth->user['password']=='123' ){
	$_SESSION["warning"] = 'Warning: you are using the default password. <a href="?option=preferences">Change it</a>';
}

check_table('cms_filters', $this->cms_filters);
$filters = sql_query("SELECT * FROM cms_filters WHERE user = '".escape($auth->user['id'])."'");
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
	<link href="/_lib/cms/css/cms-new.css" rel="stylesheet" type="text/css">

	<? if($admin_config['theme']){ ?>
	<link href="/_lib/cms/css/themes/<?=$admin_config['theme'];?>.css" rel="stylesheet" type="text/css">
	<? } ?>

	<script type="text/javascript">
	var section='<?=$vars['section'];?>';
	var fields=(<?=json_encode($fields);?>);
	</script>

	<? load_js(array('jqueryui', 'cms', 'google', 'lightbox', 'fontawesome', 'bootstrap')); ?>
	<script type="text/javascript" src="/_lib/cms/js/list.js"></script>
	<script type="text/javascript" src="/_lib/cms/js/ui.list.js"></script>
	<!-- Ionicons -->
	<link href="css/ionicons.min.css" rel="stylesheet" type="text/css" />
	
	<? //to be used for custom js ?>
	<? if( file_exists('_inc/js/cms.js') ){ ?>
	<script type="text/javascript" src="/_inc/js/cms.js"></script>
	<? } ?>
</head>
<body>
	<div class="wrapper">
		<div class="header-row">
			
			<div class="logobox">
				<? if( file_exists('images/logo.gif') ){ ?>
				<a href="/admin">
					<img src="/images/logo.gif" alt="Admin home" align="middle" style="padding:0 10px; max-height:50px;">
				</a>
				<?
				} else {
					$website=ucfirst(str_replace('www.','',$_SERVER['HTTP_HOST']));
					$arr=explode('.',$website);
	
					$website=$arr[0];
				?>
				<a href="/admin">
					<?=$website;?>
				</a>
				<? } ?>
			</div>
			
			<div class="mob-nav-icon">
				
				<i class="fa fa-bars"></i>
				
			</div>
			
			<div class="right-opts">
				
				<? if( $auth->user['admin'] ){ ?>
					<div class="name">Hello, <?=$auth->user['name'] ? $auth->user['name'].' '.$auth->user['surname'] : $auth->user['email'];?></div> 
					<a href="../">Website</a> 
					<a href="?option=logout">Log out</a>
				<? }else{ ?>
				<a href="?option=login">Log in</a>
				<? } ?>
				
			</div>
		</div>
		
		<div class="leftcol">
			<div class="menu">
			<?
			if( $auth->user['admin'] ){
			?>
			<ul>
				<?
				foreach( $vars['sections'] as $section){
					preg_match('/([a-zA-Z0-9\-\s]+)/', $section, $matches);
					$option = trim($matches[1]);
								
					if( $section=='-' ){
				?>
						<hr>
				<?
					}elseif( $auth->user['admin']==1 or $auth->user['privileges'][$option] ){
				?>
					<li <? if($option==$_GET['option']){ ?>id="current"<? } ?>><a href="?option=<?=$option;?>" title="<?=ucfirst($section);?>"><?=ucfirst($section);?></a></li>
					
					<? 
					foreach ($filters as $v) { 
						if ($v['section'] != $option) {
							continue;
						}
					?>
						<li <? if($v['filter']==http_build_query($_GET)){ ?>id="current"<? } ?>><a href="?<?=$v['filter'];?>" title="<?=ucfirst($v['name']);?>">- <?=ucfirst($v['name']);?></a>
						</li>
					<? } ?>
				<?
					}
				}
				?>
			</ul>
	
			<ul>
				<? if( $shop_enabled and ($auth->user['admin']==1 or $auth->user['privileges']['orders']) ){ ?>
				<li <? if($_GET['option']=='shop_orders'){ ?>id="current"<? } ?>><a href="?option=shop_orders">Orders</a></li>
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
	
				<? if( $auth->user['admin']==1 ){ ?>
				<li <? if($_GET['option']=='backup'){ ?>id="current"<? } ?>><a href="?option=backup">Download Database</a></li>
				<? } ?>
			</ul>
			<?
			}
			?>
			</div>
		</div>	
			
		<div class="content-wrapper">
			<section class="content">
			<div>
				<div class="col-sm-12">
					<?
					if ($_SESSION['message']) {
					?>
					<div id="cms_message"><?=$_SESSION['message'];?></div>
					<?
						unset($_SESSION['message']);
					}
					?>
					
					<?=$include_content;?>
				</div>
			</div>
			</section>
			
		</div>
		
	</div><?/* end wrapper */ ?>

</body>
</html>