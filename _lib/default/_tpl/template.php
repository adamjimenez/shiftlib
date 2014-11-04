<!DOCTYPE html>
<html>
<head>
    <title><?=$title;?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <? if($meta_description){ ?>
    <meta name="Description" content="<?=$meta_description;?>" />
    <? } ?>
    <? load_js(array('cms', 'bootstrap')); ?>

	<style>
    body {
      padding-top: 50px;
    }
    .starter-template {
      padding: 40px 15px;
      text-align: center;
    }
	</style>
</head>
<body>
    <div class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/"><?=$_SERVER["HTTP_HOST"];?></a>
            </div>
            <div class="collapse navbar-collapse">
    		<div>
    			<? if( $auth->user ){ ?>
    			<?=$auth->user['email'];?>
    			| <a href="/account">Account</a> | <a href="/logout">Sign out</a>
    			<? }else{ ?>
    			<a href="/login">Sign in</a>
    			<? } ?>
    		</div>
                <ul class="nav navbar-nav">
		<li<? current_tab('index'); ?>><a href="/" title="Home">Home</a></li>
                </ul>
            </div><!--/.nav-collapse -->
        </div>
    </div>

    <div class="container">
        <div class="starter-template">
        <?=$include_content; ?>
        </div>
    </div><!-- /.container -->

</body>
</html>