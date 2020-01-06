<?php
$result = $auth->register();
?>

<div class="container">
	<div class="row">
		<div class="col-md-6">
			<h1>Register</h1>
			
			<?php
			if ($result['code']) {
			?>
			
			<p><?=$result['message'];?></p>
			
			<?php
			} else {
			?>
			
			<form method="post" class="validate">
				<input type="hidden" name="register" value="1">
				<div class="form-group">
					<label for="name" class="col-sm-3 control-label">
						Email:
					</label>
					<div class="col-sm-9">
						<input type="email" name="email" class="form-control">
					</div>
					
					<label for="password" class="col-sm-3 control-label">
						Password:
					</label>
					<div class="col-sm-9">
						<input type="password" name="password" class="form-control">
					</div>
				</div>
				<button type="submit" class="submit btn-account pull-right">Register</button>
			</form>
			
			<?php
			}
			?>
		</div>
	</div>
</div>