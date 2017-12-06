<?
if( $_GET['user'] ){
	//check code
	$user = sql_query("SELECT id FROM users
		WHERE
			code = '".escape($_GET['code'])."' AND
			id = '".escape($_GET['user'])."' AND
			code_expire > CURDATE()
		LIMIT 1
	",1);

	if( $user and isset($_POST['continue']) ){
		//check fields are completed
		$errors=array();

		if( !$_POST['password'] ){
			$errors[]='password';
		}
		if( $values['password'] && strlen($values['password'])<6 ){
			$errors[]='password min 6 characters';
		}

		//else trigger error
		if( count( $errors ) ){
			print json_encode($errors);
			exit;
		}elseif( $_POST['validate'] ){
			print 1;
			exit;
		}else{
			//hash password
			$hash = $auth->create_hash($_POST['password']);

			// save user
			sql_query("UPDATE users SET
				password = '".escape($hash)."'
				WHERE
					id='".escape($user['id'])."'
				LIMIT 1
			");

			redirect('login');
		}
	}

	if( $user ){
	?>
	
	<div>
	
		<div class="container">
	
			<div class="row clearfix">
				<div class="col-sm-12">
					<h1>Change Password</h1>
				</div>
			</div>
	
			<div class="row clearfix">
				<div class="col-sm-4 col-sm-offset-4">
					<hr>
				</div>
			</div>
			
			
			<div class="row-fluid">
				
				<div class="col-sm-4 col-sm-offset-4">
					<div class="loginregbox">
						
						<form method="post" id="forgot_form" class="validate">
						<input type="hidden" name="continue" value="1">
							<input type="password" name="password" id="password" autofocus="autofocus" placeholder="Enter a new password" class="form-control" />
							<button class="btn loginregbtn" id="lc-width" type="submit" name="savebtn">Submit</button>
							
						</form>
						
					</div>
				</div>
			</div>
			
			
			
		</div>
	</div>
	
<?
}else{
?>


<div>

	<div class="container">

		<div class="row clearfix">
			<div class="col-sm-12">
				<h1 class="marketheader">Not so fast</h1>
			</div>
		</div>

		<div class="row clearfix">
			<div class="col-sm-4 col-sm-offset-4">
				<hr>
			</div>
		</div>
		
		
		<div class="row clearfix">
			<div class="col-sm-12">
				<p>The code is invalid or expired. Try the <a href="forgot">password reminder</a>.</p>
			</div>
		</div>
		
		
		
	</div>
</div>


<? } } else { ?>

<div id="header">
</div>

<div id="admin">
	<div class="container">
		<div class="row clearfix">
			<div class="col-md-6">
				<h1>Forgot your password?</h1>
				<div class="contactitem">
					<p>Please enter your e-mail address and we'll send you a password reminder.</p>
					<form method="post" id="forgot_form" class="form-horizontal validate">
						<input type="hidden" name="forgot_password" value="1">
						<div class="form-group">
							<label for="name" class="col-sm-3 control-label">
								Email:
							</label>
							<div class="col-sm-9">
								<input type="text" name="email" id="email" class="form-control">
							</div>
						</div>
						<? if( in_array('email',$auth->errors) ){ ?>
						<p style="color:red;">Enter your email</p>
						<? } ?>
						<button type="submit" class="submit btn-account pull-right">Send it</button>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div id="sidepic">
		<div id="zigzag"></div>
	</div>
</div>

<?
}
?>