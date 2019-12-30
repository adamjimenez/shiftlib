<?php
/*
    File:		auth.php
    Author:		Adam Jimenez
*/

session_start();

class auth
{
    /* constructor function */
    public function auth($config = [])
    {
        global $vars, $email_templates;

        $this->login_attempts_fields = [
            'email' => 'email',
            'password' => 'text',
            'ip' => 'text',
            'date' => 'timestamp',
            'id' => 'id',
        ];

        $this->generate_password = true;
        $this->cookie_domain = $_SERVER['HTTP_HOST'];
        $this->cookie_duration = 14;

        $this->hash_password = false;
        $this->salt = 'a9u03udk[';

        $this->errors = [];
        $this->expiry = 60;
        
        $this->check_login_attempts = true;
        
		$this->from_email = "";
		
		//specify pages where users are redirected
		$this->login = "login";
		$this->register_success = "thanks";
		$this->forgot_success = "index";
		
		//email activation
		$this->email_activation = false;
		
		//use a secret term to encrypt cookies
		$this->secret_phrase = "asdagre3";
		
		//for use with session and cookie vars
		$this->cookie_prefix = "site";
		
		$this->login_wherestr = "";
		
        $this->table = 'users';

        foreach ($config as $k => $v) {
            $this->$k = $v;
        }

        if (!$email_templates['Password Reminder']) {
            if ($this->hash_password) {
                $email_templates['Password Reminder'] = 'Dear {$name},
			
			    You have requested a password reset for your {$domain} member account.
			    Please use the following link:
			
				{$link}
			
				Kind regards
			
				The {$domain} Team';
            } else {
                $email_templates['Password Reminder'] = 'Dear {$name},
			
			    You have requested a password reminder for your {$domain} member account.
			
				Your password is: {$password}
			
				Kind regards
			
				The {$domain} Team';
            }
        }
        
        if (!$email_templates['Registration Confirmation']) {
            $email_templates['Registration Confirmation'] = 'Dear {$name},
		
		    Thank you for registering as a member of {$domain}.
		
		    To login to your new member account, visit https://{$domain}/login and login using the following information:
		
		    Username: {$email}
		    Password: {$password}
		
			Kind regards
			The {$domain} Team';
        }

        $this->required = $vars['required'][$this->table];

        //check for cookies
        $email = '';
        $password = '';

        if ($_COOKIE[$this->cookie_prefix . '_email'] and $_COOKIE[$this->cookie_prefix . '_password']) {
            $email = $_COOKIE[$this->cookie_prefix . '_email'];
            $password = $_COOKIE[$this->cookie_prefix . '_password'];
        } elseif ($_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW']) { //check for basic authentication
            $email = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
        } elseif ($_GET['auth_user'] and $_GET['auth_pw']) {
            $email = $_GET['auth_user'];
            $password = $_GET['auth_pw'];
        }

        if ($email and $password) {
            $result = sql_query('SELECT * FROM ' . $this->table . " WHERE
				email='" . escape($email) . "'
			", 1);

            if ($result) {
                //check password
                if ($password == md5($this->secret_phrase . $result['password'])) {
                    $_SESSION[$this->cookie_prefix . '_email'] = $result['email'];
                    $_SESSION[$this->cookie_prefix . '_password'] = $result['password'];
                }
            }
        }
    }
    
    public function init()
    {
        if ($_POST['login']) {
            $this->login();
        }

        //check if logged in
        if ($_SESSION[$this->cookie_prefix . '_user'] and time() < $_SESSION[$this->cookie_prefix . '_expires']) {
            $this->user = $_SESSION[$this->cookie_prefix . '_user'];
        } elseif ($_SESSION[$this->cookie_prefix . '_email'] and $_SESSION[$this->cookie_prefix . '_password']) {
            $this->load();
        }

        if ($_POST['register']) {
            global $cms_handlers;
            include('_inc/custom.php');
            
            $result = $this->register();

            if (true === $result) {
                if ($_POST['redirect']) {
                    redirect($_POST['redirect']);
                } elseif ($_SESSION['request']) {
                    $request = $_SESSION['request'];
                    unset($_SESSION['request']);
                    redirect($request);
                } else {
                    redirect($this->register_success);
                }
            } else {
                print $result;
                exit;
            }
        }

        if ($_POST['forgot_password']) {
            $this->forgot_password();
        }

        if ($_GET['u']) {
            $_SESSION['request'] = $_GET['u'];
        }
    }

    public function load()
    {
        $result = sql_query('SELECT * FROM ' . $this->table . " WHERE
			email='" . escape($_SESSION[$this->cookie_prefix . '_email']) . "' AND
			password='" . escape($_SESSION[$this->cookie_prefix . '_password']) . "'
			" . ($this->login_wherestr ? 'AND ' . $this->login_wherestr : '') . '
		', 1);

        if ($result) {
            $this->user = $result;
            $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
            $_SESSION[$this->cookie_prefix . '_expires'] = time() + $this->expiry;
        } else {
            $this->failed_login_attempt($_SESSION[$this->cookie_prefix . '_email'], $_SESSION[$this->cookie_prefix . '_password']);
            $this->logout();
        }
    }

    private function email_in_use($email)
    {
        $select = sql_query('SELECT * FROM ' . $this->table . " WHERE email='" . $email . "'", 1);
        return $select ? true : false;
    }

    public function create_hash($password)
    {
        return $this->hash_password ? hash('sha256', $this->salt . $password) : $password;
    }

    private function set_login($email, $pass)
    {
        $_SESSION[$this->cookie_prefix . '_email'] = $email;
        $_SESSION[$this->cookie_prefix . '_password'] = $pass;
    }

    public function failed_login_attempt($email, $password)
    {
        if (!$this->check_login_attempts) {
            return false;
        }
        
        sql_query("INSERT INTO login_attempts SET
			email='" . escape($email) . "',
			ip='" . escape($_SERVER['REMOTE_ADDR']) . "'
		");
    }

    public function check_login_attempts()
    {
    	global $cms;
    	
        if (!$this->check_login_attempts) {
            return false;
        }
        
        $cms->check_table('login_attempts', $this->login_attempts_fields);

        sql_query('DELETE FROM login_attempts WHERE
			`date`<DATE_SUB(NOW(),INTERVAL 10 MINUTE)
		');

        $rows = sql_query("SELECT id FROM login_attempts WHERE
			ip='" . escape($_SERVER['REMOTE_ADDR']) . "' AND
			`date`>DATE_SUB(NOW(),INTERVAL 10 MINUTE)
		");

        if (count($rows) >= 5) {
            die('Too many login attempts - try again in 10 minutes.');
        }
    }

    public function show_error($error)
    {
        print json_encode($error);
        exit;
    }

    public function register() //invoked by $_POST['register']
    {
        global $cms;

        $cms->set_section($this->table);
        
        $data = $_POST;
        unset($data['admin']);

        $errors = $cms->validate();
        
        if (isset($data['confirm']) and $data['confirm'] != $data['password']) {
            $errors[] = 'password passwords do not match';
        }

        if ($errors) {
            $this->show_error($errors);
        } elseif ($_POST['validate']) {
            return 1;
        }

        $data['password'] = $_POST['password'] ?: generate_password();
        
        $id = $cms->save($data);

        $reps = $_POST;

        if ($this->email_activation) {
            //activation code
            $code = substr(md5(rand(0, 10000)), 0, 10);

            sql_query('UPDATE ' . $this->table . " SET
				code = '" . escape($code) . "',
				code_expire = DATE_ADD(CURDATE(), INTERVAL 1 HOUR)
				WHERE
					id='" . $id . "'
				LIMIT 1
			");

            $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/activate?user=' . $id . '&code=' . $code;
        }

        $reps['domain'] = $_SERVER['HTTP_HOST'];

        email_template($_POST['email'], 'Registration Confirmation', $reps);

		// login
        $data['password'] = $this->create_hash($data['password']);
        
        $this->set_login($_POST['email'], $data['password']);

        return true;
    }

    public function do_forgot($email)
    {
        $error = '';
        if (!is_email($email)) {
            return 'email required';
        }
        $user = sql_query('SELECT * FROM ' . $this->table . "
				WHERE
					email = '" . escape($email) . "'
			", 1);
    
        if (!$user) {
            return 'email not recognised';
        }

        //activation code
        $code = substr(md5(rand(0, 10000)), 0, 10);
    
        sql_query('UPDATE ' . $this->table . " SET
				code = '" . escape($code) . "',
				code_expire = DATE_ADD(CURDATE(), INTERVAL 1 HOUR)
				WHERE
					id='" . $user['id'] . "'
				LIMIT 1
			");
    
        $reps['user_id'] = $user['id'];
        $reps['code'] = $code;
    
        email_template($email, 'Password Reminder', $reps);
        
        return $error ?: true;
    }
    
    public function do_reset($code = '', $password = '')
    {
        if (!$code) {
            return 'missing code';
        } else if (!$password) {
            return 'missing password';
        }
        
        //check code
        $user = sql_query("SELECT id FROM " . $this->table . "
			WHERE
				code = '" . escape($code) . "' AND
				code_expire > CURDATE()
			LIMIT 1
		", 1);
    
        if ($user) {
            //hash password
            $password = $this->create_hash($password);

            // save user
            sql_query("UPDATE " . $this->table . " SET
				password = '" . escape($password) . "'
				WHERE
					id='" . escape($user['id']) . "'
				LIMIT 1
			");
            
            return true;
        }
        
        return 'code expired';
    }

    public function forgot_password($email = null) //invoked by $_POST['forgot_password']
    {
    	// default to post value
        if (!$email) {
            $email = $_POST['email'];
        }

		// check email is valid
        if (!is_email($email)) {
            $this->show_error(['email']);
        }

		// check user exists
        $user = sql_query('SELECT * FROM ' . $this->table . "
			WHERE
				email = '" . escape($email) . "'
		", 1);

        if ($user) {
            if ($_POST['validate']) {
                print 1;
                exit;
            }
        } else {
            $this->show_error('email is not in use');
        }

        $reps = $user;
        if ($this->hash_password) {
            //activation code
            $code = substr(md5(rand(0, 10000)), 0, 10);

            sql_query('UPDATE ' . $this->table . " SET
				code = '" . escape($code) . "',
				code_expire = DATE_ADD(CURDATE(), INTERVAL 1 HOUR)
				WHERE
					id='" . $user['id'] . "'
				LIMIT 1
			");
    
            $reps['user_id'] = $user['id'];
            $reps['code'] = $code;
            $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/forgot?user=' . $user['id'] . '&code=' . $code;
        } else {
        	// deprecated
            if (!$user['password']) {
                $user['password'] = generate_password();

                sql_query('UPDATE ' . $this->table . " SET
						password = '" . addslashes($user['password']) . "'
					WHERE
						id = '" . $user['id'] . "'
					LIMIT 1
				");
            }

            $reps['password'] = $user['password'];
            $reps['domain'] = $_SERVER['HTTP_HOST'];
        }

        email_template($email, 'Password Reminder', $reps);

        if ($this->forgot_success) {
            redirect($this->forgot_success);
        }
    }
    
    public function do_login($email, $password)
    {
        $error = false;
        if ($email and $password) {
            $this->check_login_attempts();

            $password = $this->create_hash($password);

            $row = sql_query('SELECT * FROM ' . $this->table . "
				WHERE
					email='" . escape($email) . "' AND
					password='" . escape($password) . "'
			", 1);

            if ($row) {
                $this->user = $row;
                $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
                $_SESSION[$this->cookie_prefix . '_email'] = $email;
                $_SESSION[$this->cookie_prefix . '_password'] = $password;
                $_SESSION[$this->cookie_prefix . '_expires'] = time() + $this->expiry;

                if ($this->log_last_login) {
                    sql_query('UPDATE ' . $this->table . " SET
						last_login=NOW()
						WHERE
							email='" . escape($email) . "'
						LIMIT 1
					");
                }

                //cookies
                if ($remember) {
                    setcookie($this->cookie_prefix . '_email', $email, time() + (86400 * $this->cookie_duration), '/', $this->cookie_domain);
                    setcookie($this->cookie_prefix . '_password', md5($this->secret_phrase . $password), time() + (86400 * $this->cookie_duration), '/', $this->cookie_domain);
                }
            } else {
                $error = 'password incorrect';
                $this->failed_login_attempt($email, $password);
            }
        } elseif (!$email) {
            $error = 'email required';
        } elseif (!$password) {
            $error = 'password required';
        }
        
        return $error ?: true;
    }

    public function login() //invoked by $_POST['login']
    {
    	$result = $this->do_login($_POST['email'], $_POST['password']);
    	
    	if ($result!==true) {
    		$this->errors[] = $result;
    	}

        if (count($this->errors)) {
            $this->show_error($this->errors);
        } elseif ($_POST['validate']) {
            print 1;
            exit;
        }
    }

	// force user to log in
    public function check_login()
    {
        if ($this->email_activation and $this->user and !$this->user['active']) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('/activate');
        }

        if (!$this->user) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect($this->login);
        }
    }

	// log out the user
    public function logout($redirect = true)
    {
    	// unset cookies
        setcookie($this->cookie_prefix . '_email', '', time() - (86400 * $this->cookie_duration), '/', $this->cookie_domain);
        setcookie($this->cookie_prefix . '_password', '', time() - (86400 * $this->cookie_duration), '/', $this->cookie_domain);

		// unset session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

		// destroy session
        $_SESSION = [];
        session_destroy();
        
		if ($redirect) {
        	redirect('/');
		}
    }
}
