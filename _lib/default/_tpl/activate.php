<?php
if ($auth->user['active']) {
    if ($_SESSION['request']) {
        $request = $_SESSION['request'];
        unset($_SESSION['request']);
        redirect($request);
    } else {
        redirect('/');
    }
}

if ($_POST['resend']) {
    $reps = [];

    if ($auth->email_activation) {
        //activation code
        $code = substr(md5(rand(0, 10000)), 0, 10);

        sql_query("UPDATE users SET
			code = '" . escape($code) . "',
			code_expire = DATE_ADD(CURDATE(), INTERVAL 1 HOUR)
			WHERE
				id='" . $auth->user['id'] . "'
			LIMIT 1
		");

        $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/activate?user=' . $auth->user['id'] . '&code=' . $code;
    }

    $reps['domain'] = $_SERVER['HTTP_HOST'];

    email_template($auth->user['email'], 'Registration Confirmation', $reps);
}

if ($_GET['user']) {
    //check code
    $user = sql_query("SELECT id FROM users
		WHERE
			code = '" . escape($_GET['code']) . "' AND
			id = '" . escape($_GET['user']) . "' AND
			code_expire > CURDATE()
		LIMIT 1
	", 1);

    if ($user) {
        // save user
        sql_query("UPDATE users SET
			active = 1
			WHERE
				id='" . escape($user['id']) . "'
			LIMIT 1
		");

        $auth->load();

        if ($_SESSION['request']) {
            $request = $_SESSION['request'];
            //unset($_SESSION['request']);
            redirect($request);
        } else {
            ?>
    	<div>
    		<div class="container">
        		<div class="row">
        			<div class="col-sm-12 markettop">
    					<h2 class="acctitle">Thanks for verifiying your email</h2>
    				</div>
    			</div>
    		</div>
    	</div>
		<?php
        }
    } else {
        ?>
	<div>
		<div class="container">
    		<div class="row">
    			<div class="col-sm-12 markettop">
					<h2 class="acctitle">Not so fast</h2>
					<p>The code is invalid or expired. Try the <a href="forgot">password reminder</a>.</p>
				</div>
			</div>
		</div>
	</div>
	<?php
    }
} else {
    ?>
	<div>
		<div class="container">
    		<div class="row">
    			<div class="col-sm-12">
					<h1>Verify your account</h1>
					<p>Please check your email to verify your account.</p>
					<?php if ($_POST['resend']) { ?>
					<p>Message re-sent</p>
					<?php } else { ?>
					<form method="post">
						<input type="hidden" name="resend" value="1">
						<button type="submit" class="btn btn-listsub">Resend email</button>
					</form>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
    <?php
}
?>