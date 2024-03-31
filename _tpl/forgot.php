<?php
$result = $auth->forgot_password();

if ($auth->user) {
	redirect($_SESSION['request'] ?: '/');
}
?>

<div class="container">

	<div class="row clearfix">
		<div class="col-md-6">
			<h1>Forgot password</h1>
		</div>
	</div>

	<div class="row clearfix">
		
		<div class="col-md-6">
			<?php
			if ($result['code']) {
			?>
			
			<p><?=$result['message'];?></p>
				
			<?php
				// password reset
				if ($result['code'] == 3) {
				?>
					<form method="post" class="validate">
						<input type="hidden" name="reset_password" value="1">
						<input type="password" name="password" id="password" autofocus="autofocus" placeholder="Enter a new password"><br>
						<button type="submit" class="submit">Continue</button>
					</form>
				<?php
				}
			} else {
			?>
			
			<p>Please enter your e-mail address and we'll send you a password reminder.</p>
			<form method="post" class="form-horizontal validate">
				<input type="hidden" name="forgot_password" value="1">
				<div class="form-group">
					<label for="name" class="col-sm-3 control-label">
						Email:
					</label>
					<div class="col-sm-9">
						<input type="email" name="email" class="form-control">
					</div>
				</div>
				<button type="submit" class="submit">Send it</button>
			</form>
			
			<?php } ?>
		</div>
		
	</div>
</div>