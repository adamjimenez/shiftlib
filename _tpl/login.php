<?php
// redirect to home if logged in
$error = '';
try {
	$result = $auth->login();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// handle login
if ($result['code'] === 1) {
	redirect($_SESSION['request'] ?: '/');
// new registration via single sign on
} elseif ($result['code'] === 2) {
	redirect('/register/success');
}
?>

<div class="container">
	<?php if ($error) { ?>
	<div class="alert alert-danger" role="alert">
		<?=$error;?>
	</div>
	<?php } ?>

	<div class="row clearfix">
		<div class="col-md-6">
			<h1>Sign in</h1>
		</div>
	</div>

	<div class="row clearfix">
		
		<div class="col-md-6">
			<form method="post" class="validate clearfix form-horizontal">
				<input type="hidden" name="login" value="1">
				
				<div class="form-group">
					<input type="text" placeholder="Email address" name="email">
				</div>
				<div class="form-group">
					<input type="password" placeholder="Password" name="password">
				</div>
				
				<p>
					<a href="/forgot">Forgot password?</a>
				</p>
				
				<p>
					<button class="btn btn-primary" type="submit">Sign in</button>
				</p>
				
				<p>
					<a href="/register">
						Create an account
					</a>
				</p>
			</form>
			
			<hr>
			
			<?php 
			if ($auth_config["facebook_appId"] || $auth_config["google_appId"]) { 
			?>
			<h3>Or sign in with:</h3>
			<?php
			}
			?>
			
			<?php 
			if ($auth_config["facebook_appId"]) { 
			?>
			<div class="row">
			
				<div class="col">
					<a class="btn btn-sociallogin facebook" href="login?provider=facebook">
						<i class="fab fa-facebook-square fa-lg"></i>&nbsp;&nbsp;Sign in with Facebook
					</a>
				</div>
			
			</div>
			<?php
			}
			?>
			
			<?php 
			if ($auth_config["google_appId"]) { 
			?>
			<div class="row">
			
				<div class="col">
					<a class="btn btn-sociallogin google" href="login?provider=google">
						<i class="fab fa-google fa-lg"></i>&nbsp;&nbsp;Sign in with Google
					</a>
				</div>
			
			</div>
			<?php
			}
			?>
			
		</div>
		
		
	</div>
	
</div>