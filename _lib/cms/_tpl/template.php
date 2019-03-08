<?php
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

	<?php if($admin_config['theme']){ ?>
	<link href="/_lib/cms/css/themes/<?=$admin_config['theme'];?>.css" rel="stylesheet" type="text/css">
	<?php } ?>

	<script type="text/javascript">
	var section='<?=$vars['section'];?>';
	var fields=(<?=json_encode($fields);?>);
	</script>

	<?php load_js(array('jqueryui', 'cms', 'google', 'lightbox', 'fontawesome', 'bootstrap')); ?>
	<script type="text/javascript" src="/_lib/cms/js/list.js"></script>
	<script type="text/javascript" src="/_lib/cms/js/ui.list.js"></script>
	
	<?php //to be used for custom js ?>
	<?php if( file_exists('_inc/js/cms.js') ){ ?>
	<script type="text/javascript" src="/_inc/js/cms.js"></script>
	<?php } ?>
</head>
<body>
	<div class="wrapper">
		<div class="header-row">
			
			<div class="logobox">
				<?php if( file_exists('images/logo.gif') ){ ?>
				<a href="/admin">
					<img src="/images/logo.gif" alt="Admin home" align="middle" style="padding:0 10px; max-height:50px;">
				</a>
				<?php
				} else {
					$website=ucfirst(str_replace('www.','',$_SERVER['HTTP_HOST']));
					$arr=explode('.',$website);
	
					$website=$arr[0];
				?>
				<a href="/admin">
					<?=$website;?>
				</a>
				<?php } ?>
			</div>
			
			<div class="mob-nav-icon">
				
				<i class="fa fa-bars"></i>
				
			</div>
			
			<div class="right-opts">
				
				<?php if( $auth->user['admin'] ){ ?>
					<div class="name">Hello, <?=$auth->user['name'] ? $auth->user['name'].' '.$auth->user['surname'] : $auth->user['email'];?></div> 
					<a href="../">Website</a> 
					<a href="?option=logout">Log out</a>
				<?php }else{ ?>
				<a href="?option=login">Log in</a>
				<?php } ?>
				
			</div>
		</div>
		
		<div class="leftcol">
			<div class="menu">
			<?php
			if( $auth->user['admin'] ){
			?>
			<ul>
				<?php
				foreach( $vars['sections'] as $section){
					preg_match('/([a-zA-Z0-9\-\s]+)/', $section, $matches);
					$option = trim($matches[1]);
								
					if( $section=='-' ){
				?>
						<hr>
				<?php
					} elseif( $auth->user['admin']==1 or $auth->user['privileges'][$option] ) {
				?>
					<li <?php if($option==$_GET['option']){ ?>id="current"<?php } ?>>
						<a href="?option=<?=$option;?>" title="<?=ucfirst($section);?>">
							<?=ucfirst($section);?>
							<?php 
							if(in_array('read', $vars['fields'][$section])) {
								$unread = $this->get($section, array('read'=>0), true);
								
								if ($unread) {
							?>
								(<?=$unread;?>)
							<?php 
								}
							} 
							?>
						</a>
					</li>
					
					<?php 
					foreach ($filters as $v) { 
						if ($v['section'] != $option) {
							continue;
						}
						
						parse_str($v['filter'], $conditions);
						$result = $this->get($v['section'], $conditions, true);
					?>
						<li <?php if($v['filter']==http_build_query($_GET)){ ?>id="current"<?php } ?>>
							<a href="?<?=$v['filter'];?>" title="<?=ucfirst($v['name']);?>">- <?=ucfirst($v['name']);?> <?php if ($result) { ?>(<?=$result;?>)<?php } ?>
							</a>
						</li>
					<?php } ?>
				<?php
					}
				}
				?>
			</ul>
	
			<ul>
				<?php if( $shop_enabled and ($auth->user['admin']==1 or $auth->user['privileges']['orders']) ){ ?>
				<li <?php if($_GET['option']=='shop_orders'){ ?>id="current"<?php } ?>><a href="?option=shop_orders">Orders</a></li>
				<?php } ?>
				<?php if( ($auth->user['admin']==1 or $auth->user['privileges']['email_templates']) ){ ?>
				<li <?php if($_GET['option']=='email_templates'){ ?>id="current"<?php } ?>><a href="?option=email templates">Email Templates</a></li>
				<?php } ?>
				<?php if( $auth->user['admin']==1 and $sms_config['provider'] ){ ?>
				<li <?php if($_GET['option']=='sms templates'){ ?>id="current"<?php } ?>><a href="?option=sms templates">SMS Templates</a></li>
				<?php } ?>
	
				<?php if( $auth->user['admin']==1 or $auth->user['privileges']['uploads'] ){ ?>
				<li><a href="#" class="upload">Uploads</a></li>
				<?php } ?>
	
				<?php if( $vars['configure_dropdowns'] and ($auth->user['admin']==1 or $auth->user['privileges']['email_templates']) ){ ?>
				<li <?php if($_GET['option']=='dropdowns'){ ?>id="current"<?php } ?>><a href="?option=dropdowns">Dropdowns</a></li>
				<?php } ?>
	
				<?php /*if( $auth->user['admin'] ){ ?>
				<li <?php if($_GET['option']=='preferences'){ ?>id="current"<?php } ?>><a href="?option=preferences">Preferences</a></li>
				<?php }*/ ?>
	
				<?php if( $auth->user['admin']==1 ){ ?>
				<li <?php if($_GET['option']=='configure'){ ?>id="current"<?php } ?>><a href="?option=configure">Configure</a></li>
				<?php } ?>
	
				<?php /*if( $auth->user['admin']==1 ){ ?>
				<li <?php if($_GET['option']=='backup'){ ?>id="current"<?php } ?>><a href="?option=backup">Download Database</a></li>
				<?php }*/ ?>
			</ul>
			<?php
			}
			?>
			</div>
		</div>	
			
		<div class="content-wrapper">
			<section class="content">
			<div>
				<div class="col-sm-12">
					<?php
					if ($_SESSION['message']) {
					?>
					<div id="cms_message"><?=nl2br($_SESSION['message']);?></div>
					<?php
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