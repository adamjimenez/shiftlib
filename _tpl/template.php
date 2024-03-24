<!DOCTYPE html>
<html>
<head>
    <title><?=$title;?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($meta_description) { ?>
    <meta name="Description" content="<?=$meta_description;?>" />
    <?php } ?>
    <?php load_js(['cms', 'bootstrap', 'fontawesome']); ?>

	<style>
    body {
      padding-top: 50px;
      background-color: #333333;
      color: #ffffff;
    }
	</style>
</head>
<body>
    <div class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <a type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <i class="fas fa-bars"></i>
                </a>
                <a class="navbar-brand" href="/"><?=$_SERVER['HTTP_HOST'];?></a>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
            		<li<?=current_tab('index'); ?>><a href="/" title="Home">Home</a></li>
        			<?php if ($auth->user) { ?>
        			    <li><a href="/account/">Account</a></li>
        			    <li><a href="/logout">Sign out</a></li>
        			<?php } else { ?>
        			<li><a href="/login">Sign in</a></li>
        			<?php } ?>
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