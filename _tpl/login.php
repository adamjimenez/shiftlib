<?php
// redirect to home if logged in
$result = $auth->login();

// handle login
if ($result['code']===1) {
	redirect('/');
}
?>

<div class="container-fluid">

	<div class="row clearfix">
		<div class="col-md-6">
			<h1>Sign in</h1>
		</div>
	</div>

	<div class="row clearfix">
		
		<div class="col-md-6">
			<form method="post" class="validate clearfix form-horizontal">
				<input type="hidden" name="login" value="1">
				
				<input type="text" placeholder="Email address" name="email"><br>
				<input type="password" placeholder="Password" name="password"><br>
				
				<p>
					<a href="/forgot">Forgot password?</a>
				</p>
				
				<p>
					<button class="btn" type="submit">Sign in</button>
				</p>
				
				<p>
					<a href="/register">
						Create an account
					</a>
				</p>
			</form>
			
			<hr>
			
			<h3>Or sign in with:</h3>
			
			<div class="row">
			
				<div class="col">
					<a class="btn btn-sociallogin facebook" href="login?provider=facebook">
						<i class="fab fa-facebook-square fa-lg"></i>&nbsp;&nbsp;Sign in with Facebook
					</a>
				</div>
			
			</div>
			
			<div class="row">
			
				<div class="col">
					<a class="btn btn-sociallogin google" href="login?provider=google">
						<i class="fab fa-google fa-lg"></i>&nbsp;&nbsp;Sign in with Google
					</a>
				</div>
			
			</div>
			
		</div>
		
		
	</div>
	
</div>