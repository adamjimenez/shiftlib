<?php
/*
	File:		auth.php
	Author:		Adam Jimenez
	Modified:	7/10/2007
*/

session_start();

global $email_templates;

if( !$email_templates['Password Reminder'] ){
	$email_templates['Password Reminder']='Dear {$name},

    You have requested a password reminder for your {$domain} member account.

	Your password is: {$password}

	Kind regards

	The {$domain} Team';
}

if( !$email_templates['Registration Confirmation'] ){
	$email_templates['Registration Confirmation']='Dear {$name},

    Thank you for registering as a member of {$domain}.

    To login to your new member account, visit http://{$domain}/login and login using the following information:

    Username: {$email}
    Password: {$password}

	Kind regards
	The {$domain} Team';
}

class auth{
	/* constructor function */
	function auth()
	{
		global $auth_config, $vars;

		$this->login_attempts_fields=array(
			'email'=>'email',
			'password'=>'text',
			'ip'=>'text',
			'date'=>'timestamp',
			'id'=>'id'
		);

		$this->generate_password=true;
		$this->cookie_domain=$_SERVER['HTTP_HOST'];

		$this->hash_password=false;
		$this->salt='a9u03udk[';

		$this->errors=array();

		foreach( $auth_config as $k=>$v ){
			$this->$k=$v;
		}

		$this->required=$vars["required"][$this->table];

		if( $this->db ){
			mysql_select_db($this->db) or trigger_error("SQL", E_USER_ERROR);
		}

		/*
		$user_fields=array(
			'name'=>'text',
			'surname'=>'text',
			'email'=>'text',
			'password'=>'password',
			'address'=>'textarea',
			'city'=>'text',
			'postcode'=>'text',
			'tel'=>'text',
			'admin'=>'checkbox',
		);*/

		//check for cookies
		$email='';
		$password='';

		if( $_COOKIE[$this->cookie_prefix.'_email'] AND $_COOKIE[$this->cookie_prefix.'_password'] ){
			$email=$_COOKIE[$this->cookie_prefix.'_email'];
			$password=$_COOKIE[$this->cookie_prefix.'_password'];
		}elseif( $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW'] ){ //check for basic authentication
			$email=$_SERVER['PHP_AUTH_USER'];
			$password=$_SERVER['PHP_AUTH_PW'];
		}elseif( $_GET['auth_user'] and $_GET['auth_pw'] ){
			$email=$_GET['auth_user'];
			$password=$_GET['auth_pw'];
		}

		if( $email and $password ){
			$select=mysql_query("SELECT * FROM ".$this->table." WHERE
				email='".escape($email)."'
			") or trigger_error("SQL", E_USER_ERROR);

			if( mysql_num_rows($select) ){
				$result=mysql_fetch_array($select);

				//check password
				if( $password==md5($this->secret_phrase.$result['password']) ){
					$_SESSION[$this->cookie_prefix.'_email']=$result['email'];
					$_SESSION[$this->cookie_prefix.'_password']=$result['password'];

				}elseif( $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW'] ){
					//die('bad authentication login');
				}
			}
		}

		if( $_POST['login'] ){
			$this->login();
		}

		//check if logged in
		if( $_SESSION[$this->cookie_prefix.'_email'] AND $_SESSION[$this->cookie_prefix.'_password'] ){
			$this->check_login_attempts();

			$select=mysql_query("SELECT * FROM ".$this->table." WHERE
				email='".escape($_SESSION[$this->cookie_prefix.'_email'])."' AND
				password='".escape($_SESSION[$this->cookie_prefix.'_password'])."'
				".($this->login_wherestr ? 'AND '.$this->login_wherestr : '')."
			") or trigger_error("SQL", E_USER_ERROR);

			if( mysql_num_rows($select)==1 ){
				$this->user=mysql_fetch_array($select);

				if( $this->user['admin']>1 and table_exists('cms_privileges') ){
					$select_privileges=mysql_query("SELECT * FROM cms_privileges WHERE
						user='".escape($this->user['id'])."'
					") or trigger_error("SQL", E_USER_ERROR);

					while( $row=mysql_fetch_array($select_privileges) ){
						$this->user['privileges'][$row['section']]=$row['access'];

						$pairs=explode('&',$row['filter']);

						foreach( $pairs as $pair ){
							$arr=explode('=',$pair);

							$this->user['filters'][$row['section']][underscored($arr[0])]=urldecode($arr[1]);
						}

					}
				}
			}else{
				$this->failed_login_attempt($_SESSION[$this->cookie_prefix.'_email'],$_SESSION[$this->cookie_prefix.'_password']);
				$this->logout();
			}
		}

		if( $_POST['register'] ){
			$this->register();
		}

		if( $_POST['forgot_password'] ){
			$this->forgot_password();
		}

		if( $_POST['update_details'] ){
			$this->update_details();
		}

		global $request;

		if(
			(
				$this->check_all and
				array_search($request,$this->skip_checks)===false
			) and (
				$this->check_all and
				$this->skip_check!==true
			) and
			$_SERVER['REQUEST_URI']!=$this->login  and
			$_SERVER['REQUEST_URI']!='/sitemap.xml' and
			$_SERVER['REQUEST_URI']!='/robots.txt'
		){
			$this->check_login();
		}

		if(
			$this->check_all_admin and
			!$this->skip_check  and
			$_SERVER['REQUEST_URI']!=$this->login
		){
			$this->check_admin();
		}
	}

	/* private function */
	function email_in_use( $email )
	{
		$select=mysql_query("SELECT * FROM ".$this->table." WHERE email='".$email."'") or trigger_error("SQL", E_USER_ERROR);

		if( mysql_num_rows($select)>0 ){
			return true;
		}else{
			return false;
		}
	}

	function create_hash($password)
	{
		return hash('sha256', $this->salt.$password);
	}

	/* private function */
	function set_login($email,$pass)
	{
		$_SESSION[$this->cookie_prefix.'_email']=$email;
		$_SESSION[$this->cookie_prefix.'_password']=$pass;
	}

	function email_confirmation()
	{

	}

	function failed_login_attempt($email,$password)
	{
		check_table('login_attempts', $this->login_attempts_fields );

		mysql_query("INSERT INTO login_attempts SET
			email='".escape($email)."',
			password='".escape($password)."',
			ip='".escape($_SERVER['REMOTE_ADDR'])."'
		") or trigger_error("SQL", E_USER_ERROR);
	}

	function check_login_attempts()
	{
		check_table('login_attempts', $this->login_attempts_fields );

		mysql_query("DELETE FROM login_attempts WHERE
			`date`<DATE_SUB(NOW(),INTERVAL 10 MINUTE)
		") or trigger_error("SQL", E_USER_ERROR);

		$select=mysql_query("SELECT id FROM login_attempts WHERE
			ip='".escape($_SERVER['REMOTE_ADDR'])."' AND
			`date`>DATE_SUB(NOW(),INTERVAL 10 MINUTE)
		") or trigger_error("SQL", E_USER_ERROR);

		if( mysql_num_rows($select)>=5 ){
			die('Too many login attempts - try again in 10 minutes.');
		}
	}

	function show_error( $error )
	{
		$_SESSION['error']=$error;
	}

	function register() //invoked by $_POST['register']
	{
		global $cms;

		$cms->set_section($this->table);

		$errors=$cms->validate();

		if( $errors ){
            print json_encode($errors);
            exit;
		}

		if( $this->generate_password ){
			$_POST['password']=generate_password();
		}

		if( $this->hash_password ){
			$_POST['password']=$this->create_hash($_POST['password']);
		}

		$id=$cms->save();

		$reps = $_POST;
        $reps['domain'] = $_SERVER["HTTP_HOST"];

		email_template( $_POST['email'],'Registration Confirmation',$reps );

		if( $this->registration_notification ){
			global $from_email;

			$headers="From: ".$from_email."\n";

			$msg='New user registration:
			http://'.$_SERVER['HTTP_HOST'].'/admin?option=users&view=true&id='.$id;

			$msg=str_replace("\t",'',$msg);

			mail($from_email,'New user registration',$msg,$headers);
		}

		//$_SESSION[$this->cookie_prefix.'_email']=$_POST['email'];
		//$_SESSION[$this->cookie_prefix.'_password']=$_POST['password'];

		header("location:".$this->register_success);
		exit;
	}

	function update_details()
	{
		global $cms;

		$cms->set_section($this->table,$this->user['id']);

		$errors=$cms->validate();

		if( $errors ){
			$this->show_error('Check the following fields:\n'.implode("\n",$errors));
			return false;
		}

		if( $_POST['password'] ){
			if( $this->hash_password ){
				$_POST['password']=$this->create_hash($_POST['password']);
			}
		}else{
			unset($_POST['password']);
		}

		$id = $cms->save();

		//update session email and password if neccessary
		if( $_POST['email'] ){
			$_SESSION[$this->cookie_prefix.'_email']=$_POST['email'];
		}

		if( $_POST['password'] ){
			$_SESSION[$this->cookie_prefix.'_password']=$_POST['password'];
		}

		$select=mysql_query("SELECT * FROM ".$this->table."
			WHERE
				id='".escape($id)."'
		") or trigger_error("SQL", E_USER_ERROR);

		$this->user=mysql_fetch_array($select);

		$this->update_success=true;
	}

	function forgot_password() //invoked by $_POST['forgot_password']
	{
		if( !is_email($_POST['email']) ){
			$this->show_error('Email address not entered or invalid.');

			$this->errors[]='email';
		}

		$select=mysql_query("SELECT * FROM ".$this->table."
			WHERE
				email='".escape($_POST['email'])."'
		") or trigger_error("SQL", E_USER_ERROR);

		if( mysql_num_rows($select)==1 ){
			$user=mysql_fetch_array($select);
		}else{
			$this->show_error('Email address is not in use.');

			$this->errors[]='email not in use';

			//redirect('forgot');
			return false;
		}

		if( !$user['password'] ){
			$password=generate_password();

			mysql_query("UPDATE ".$this->table." SET
					password='".addslashes($password)."'
				WHERE
					id='".$user['id']."'
				LIMIT 1
			") or trigger_error("SQL", E_USER_ERROR);

			$user['password']=$password;
		}

		$reps=$user;
		$reps['password'] = $user['password'];
        $reps['domain'] = $_SERVER["HTTP_HOST"];

		email_template( $_POST['email'],'Password Reminder', $reps );

		header("location:".$this->forgot_success);
		exit;
	}

	function login() //invoked by $_POST['login']
	{
		if( $_POST['email'] and $_POST['password'] ){
			$this->check_login_attempts();

			if( $this->hash_password ){
				$_POST['password']=$this->create_hash($_POST['password']);
			}

			$select=mysql_query("SELECT * FROM ".$this->table."
				WHERE
					email='".escape($_POST['email'])."' AND
					password='".escape($_POST['password'])."'
					".($this->login_wherestr ? 'AND '.$this->login_wherestr : '')."
			") or trigger_error("SQL", E_USER_ERROR);

			if( mysql_num_rows($select)==1 ){
				$_SESSION[$this->cookie_prefix.'_email']=$_POST['email'];
				$_SESSION[$this->cookie_prefix.'_password']=$_POST['password'];

				if( $this->log_last_login ){
					mysql_query("UPDATE ".$this->table." SET
						last_login=NOW()
						WHERE
							email='".escape($_POST['email'])."'
						LIMIT 1
					") or trigger_error("SQL", E_USER_ERROR);
				}

				//cookies
				if( $_POST['remember'] ){
					setcookie($this->cookie_prefix.'_email' ,$_POST['email'], time()+(86400*14), '/', $this->cookie_domain );
					setcookie($this->cookie_prefix.'_password' ,md5($this->secret_phrase.$_POST['password']), time()+(86400*14), '/', $this->cookie_domain );
				}

				if( $_SESSION['request'] ){
					$request=$_SESSION['request'];
					unset($_SESSION['request']);
					redirect($request);
				}
			}else{
				$this->show_error('login incorrect');

				$this->errors[]='email';
				$this->errors[]='password';

				$this->failed_login_attempt($_POST['email'],$_POST['password']);
			}
		}else{
			$this->show_error('missing email or password');

			$this->errors[]='email';
			$this->errors[]='password';
		}
	}

	function login_google()
	{
		if( $_GET['u'] ){
			$_SESSION['request']=$_GET['u'];
		}

		require_once(dirname(__FILE__).'/../modules/openid/openid.php');

		try {
			if(!isset($_GET['openid_mode'])) {
				$openid = new LightOpenID;

				$openid->required = array('namePerson/first', 'namePerson/last', 'contact/email');
				$openid->identity = 'https://www.google.com/accounts/o8/id';

				header('Location: ' . $openid->authUrl());
			} elseif($_GET['openid_mode'] == 'cancel') {
				echo 'User has canceled authentication!';
			} else {
				$openid = new LightOpenID;

				if( $openid->validate() ){
					$attribs=$openid->getAttributes();

					$name=$attribs['namePerson/first'];
					$surname=$attribs['namePerson/last'];
					$email=$attribs['contact/email'];

					$select=mysql_query("SELECT * FROM ".$this->table." WHERE
						email='".escape($email)."'
						LIMIT 1
					");

					//debug($openid->__get('identity'));

					if( mysql_num_rows($select) ){
						$user=mysql_fetch_array($select);
						$password=$user['password'];
					}else{
						$password=generate_password();

						mysql_query("INSERT INTO ".$this->table." SET
							email='".escape($email)."',
							name='".escape($name)."',
							surname='".escape($surname)."',
							password='".escape($password)."'
						") or trigger_error("SQL", E_USER_ERROR);

						$select=mysql_query("SELECT * FROM ".$this->table." WHERE
							email='".escape($email)."'
						");
						$user=mysql_fetch_array($select);

						$this->trigger_event('registration',$user);
					}

					if( $this->log_last_login ){
						mysql_query("UPDATE ".$this->table." SET
							last_login=NOW()
							WHERE
								email='".escape($email)."'
							LIMIT 1
						") or trigger_error("SQL", E_USER_ERROR);
					}

					$_SESSION[$this->cookie_prefix.'_email']=$email;
					$_SESSION[$this->cookie_prefix.'_password']=$password;

					setcookie($this->cookie_prefix.'_email' ,$email, time()+(86400*14), '/', $this->cookie_domain );
					setcookie($this->cookie_prefix.'_password' ,md5($this->secret_phrase.$password), time()+(86400*14), '/', $this->cookie_domain );

					if( $_SESSION['request'] ){
						$request=$_SESSION['request'];
						unset($_SESSION['request']);
						redirect($request);
					}else{
						redirect('/');
					}

					$this->check_login();
				}else{
					echo 'validation failed';
				}
			}
		} catch(ErrorException $e) {
			echo $e->getMessage();
		}
	}

	function login_facebook()
	{
		if( $_GET['u'] ){
			$_SESSION['request']=$_GET['u'];
		}

		require_once(dirname(__FILE__).'/../modules/facebook/src/facebook.php');

		// Create our Application instance (replace this with your appId and secret).
		$facebook = new Facebook(array(
			'appId'  => $this->facebook_appId,
			'secret' => $this->facebook_secret
		));

		// Get User ID
		$user = $facebook->getUser();

		// We may or may not have this data based on whether the user is logged in.
		//
		// If we have a $user id here, it means we know the user is logged into
		// Facebook, but we don't know if the access token is valid. An access
		// token is invalid if the user logged out of Facebook.

		if ($user) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$user_profile = $facebook->api('/me');
			} catch (FacebookApiException $e) {
				error_log($e);
				$user = null;
			}
		}

		// Login or logout url will be needed depending on current user state.
		if ($user) {
			$logoutUrl = $facebook->getLogoutUrl();
		} else {
			$loginUrl = $facebook->getLoginUrl(array('scope'=>'email,create_event'));
		}

		if( !$user ) {
			redirect($loginUrl);
		} elseif($user_profile['email']) {
			$name=$user_profile['first_name'];
			$surname=$user_profile['last_name'];
			$email=$user_profile['email'];

			$select=mysql_query("SELECT * FROM ".$this->table." WHERE
				email='".escape($email)."'
			");

			//debug($openid->__get('identity'));

			if( mysql_num_rows($select) ){
				$user=mysql_fetch_array($select);
				$password=$user['password'];
			}else{
				$password=generate_password();

				mysql_query("INSERT INTO ".$this->table." SET
					email='".escape($email)."',
					name='".escape($name)."',
					surname='".escape($surname)."',
					password='".escape($password)."'
				") or trigger_error("SQL", E_USER_ERROR);

				$select=mysql_query("SELECT * FROM ".$this->table." WHERE
					email='".escape($email)."'
				");
				$user_id=mysql_fetch_array($select);

				$this->trigger_event('registration',$user_id);
			}

			if( $this->log_last_login ){
				mysql_query("UPDATE ".$this->table." SET
					last_login=NOW()
					WHERE
						email='".escape($email)."'
					LIMIT 1
				") or trigger_error("SQL", E_USER_ERROR);
			}

			$_SESSION[$this->cookie_prefix.'_email']=$email;
			$_SESSION[$this->cookie_prefix.'_password']=$password;

			setcookie($this->cookie_prefix.'_email' ,$email, time()+(86400*14), '/', $this->cookie_domain );
			setcookie($this->cookie_prefix.'_password' ,md5($this->secret_phrase.$password), time()+(86400*14), '/', $this->cookie_domain );

			if( $_SESSION['request'] ){
				$request=$_SESSION['request'];
				unset($_SESSION['request']);
				redirect($request);
			}else{
				redirect('/');
			}

			$this->check_login();
		}else{
			echo 'validation failed';
		}
	}

	function check_login()
	{
		if( $this->db ){
			mysql_select_db($this->db) or trigger_error("SQL", E_USER_ERROR);
		}

		if( $_SESSION[$this->cookie_prefix.'_email'] and $_SESSION[$this->cookie_prefix.'_password'] ){
			$select=mysql_query("SELECT * FROM ".$this->table." WHERE
				email='".$_SESSION[$this->cookie_prefix.'_email']."' AND
				password='".$_SESSION[$this->cookie_prefix.'_password']."'
				".($this->login_wherestr ? 'AND '.$this->login_wherestr : '')."
			");

			if( mysql_num_rows($select)!=1 ){
				redirect($this->login);
			}
		}else{
			if( !$_SESSION['request'] ){
				$_SESSION['request']='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}

			redirect($this->login);
			print 'access denied';
			exit;
		}
	}

	function check_admin()
	{
		global $vars;

		if( !table_exists($this->table) ){
			check_table($this->table, $vars['fields'][$this->table]);

			mysql_query("ALTER TABLE `users` ADD UNIQUE `email` ( `email` )") or trigger_error("SQL", E_USER_ERROR);
		}

		$select_admin=mysql_query("SELECT * FROM ".$this->table." WHERE admin>0 LIMIT 1") or trigger_error("SQL", E_USER_ERROR);
		if( mysql_num_rows($select_admin)==0 ){
			mysql_query("INSERT INTO ".$this->table." SET email='admin', password='123', admin='1'");
		}

		if( $_SESSION[$this->cookie_prefix.'_email'] AND $_SESSION[$this->cookie_prefix.'_password'] ){
			$select=mysql_query("SELECT * FROM ".$this->table." WHERE
				email='".$_SESSION[$this->cookie_prefix.'_email']."' AND
				password='".$_SESSION[$this->cookie_prefix.'_password']."' AND
				admin!=0
			");

			if( mysql_num_rows($select) ){
				return true;
			}
		}

		$_SESSION['request']=$_SERVER['REQUEST_URI'];
		redirect('?option=login');
	}

	function logout()
	{
		$_SESSION=array();

  		setcookie($this->cookie_prefix.'_email' ,'', time()-(86400*14), '/', $this->cookie_domain);
		setcookie($this->cookie_prefix.'_password' ,'', time()-(86400*14), '/', $this->cookie_domain);

  		setcookie($this->cookie_prefix.'_email' ,'', time()-(86400*14), '/');
		setcookie($this->cookie_prefix.'_password' ,'', time()-(86400*14), '/' );

  		setcookie($this->cookie_prefix.'_email' ,'', time()-(86400*14), '/', str_replace('www','',$this->cookie_domain) );
  		setcookie($this->cookie_prefix.'_password' ,'', time()-(86400*14), '/', str_replace('www','',$this->cookie_domain) );

		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		session_destroy();
		redirect('/');
	}

	function trigger_event($event)
	{
		global $auth_config;

		$args=func_get_args();

		if( is_array($auth_config['handlers']) ){
			foreach( $auth_config['handlers'] as $handler ){
				if( $handler['event']==$event ){
					$handler['handler']($args[1]);
				}
			}
		}
	}

	function get_formkey()
	{
		$token = dechex($this->user['id']).'.'.dechex(mt_rand());
		$hash = sha1($this->form_secret.'-'.$token);
		return htmlspecialchars($token.'-'.$hash);
	}

	function check_formkey($formkey)
	{
		$parts = explode('-', $formkey);

		if (count($parts)==2) {
			list($token, $hash) = $parts;

			$arr=explode('.', $token);
			$userid = hexdec($arr[0]);

			if($userid==$this->user['id'] and $hash==sha1($this->form_secret.'-'.$token)){
				return true;
			}
		}

		return false;
	}
}
?>