<?php
/*
    File:		auth.php
    Author:		Adam Jimenez
    Modified:	7/10/2007
*/

session_start();

class auth
{
    /* constructor function */
    public function auth()
    {
        global $auth_config, $vars, $email_templates;

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

        foreach ($auth_config as $k => $v) {
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
		
		    To login to your new member account, visit http://{$domain}/login and login using the following information:
		
		    Username: {$email}
		    Password: {$password}
		
			Kind regards
			The {$domain} Team';
        }

        if (!$this->table) {
            $this->table = 'users';
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

        if ($_POST['update_details']) {
            $this->update_details();
        }

        if ($_GET['u']) {
            $_SESSION['request'] = $_GET['u'];
        }

        global $request;

        if (
            (
                $this->check_all and
                !in_array($request, $this->skip_checks)
            ) and (
                $this->check_all and
                true !== $this->skip_check
            ) and
            $_SERVER['REQUEST_URI'] != $this->login and
            '/sitemap.xml' != $_SERVER['REQUEST_URI'] and
            '/robots.txt' != $_SERVER['REQUEST_URI']
        ) {
            $this->check_login();
        }

        if (
            $this->check_all_admin and
            !$this->skip_check and
            $_SERVER['REQUEST_URI'] != $this->login
        ) {
            $this->check_admin();
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

            if ($this->user['admin'] > 1 and table_exists('cms_privileges')) {
                $rows = sql_query("SELECT * FROM cms_privileges WHERE
					user='" . escape($this->user['id']) . "'
				");

                foreach ($rows as $row) {
                    $this->user['privileges'][$row['section']] = $row['access'];

                    $pairs = explode('&', $row['filter']);

                    foreach ($pairs as $pair) {
                        $arr = explode('=', $pair);

                        $this->user['filters'][$row['section']][underscored($arr[0])] = urldecode($arr[1]);
                    }
                }
            }

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
        return hash('sha256', $this->salt . $password);
    }

    private function set_login($email, $pass)
    {
        $_SESSION[$this->cookie_prefix . '_email'] = $email;
        $_SESSION[$this->cookie_prefix . '_password'] = $pass;
    }

    public function failed_login_attempt($email, $password)
    {
    	global $cms;
    	
        if (!$auth_config['check_login_attempts']) {
            return false;
        }
        
        $cms->check_table('login_attempts', $this->login_attempts_fields);

        sql_query("INSERT INTO login_attempts SET
			email='" . escape($email) . "',
			password='" . escape($password) . "',
			ip='" . escape($_SERVER['REMOTE_ADDR']) . "'
		");
    }

    public function check_login_attempts()
    {
    	global $cms;
    	
        if (!$auth_config['check_login_attempts']) {
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
        if ('html' == $_POST['f']) {
            redirect($this->login);
        } else {
            print json_encode($this->errors);
        }
        exit;
    }

    public function do_register($data) //invoked by $_POST['register']
    {
        global $cms;

        unset($data['admin']);
        $cms->set_section($this->table, null, array_keys($data));
        
        $errors = $cms->validate();
        
        if (isset($data['confirm']) and $data['confirm'] != $data['password']) {
            $errors[] = 'password passwords do not match';
        }

        if ($errors) {
            return $errors;
        }

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

            $reps['link'] = 'http://' . $_SERVER['HTTP_HOST'] . '/activate?user=' . $id . '&code=' . $code;
        }

        $reps['domain'] = $_SERVER['HTTP_HOST'];

        email_template($data['email'], 'Registration Confirmation', $reps);

        $_SESSION[$this->cookie_prefix . '_email'] = $_POST['email'];
        
        if ($this->hash_password) {
            $_SESSION[$this->cookie_prefix . '_password'] = $this->create_hash($data['password']);
        } else {
            $_SESSION[$this->cookie_prefix . '_password'] = $data['password'];
        }

        return true;
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
            return json_encode($errors);
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

        if ($this->registration_notification) {
            global $from_email;

            $headers = 'From: ' . $from_email . "\n";

            $msg = 'New user registration:
			http://' . $_SERVER['HTTP_HOST'] . '/admin?option=users&view=true&id=' . $id;

            $msg = str_replace("\t", '', $msg);

            mail($from_email, 'New user registration', $msg, $headers);
        }

        if ($this->register_login) {
            $_SESSION[$this->cookie_prefix . '_email'] = $_POST['email'];
            
            if ($this->hash_password) {
                $_SESSION[$this->cookie_prefix . '_password'] = $this->create_hash($data['password']);
            } else {
                $_SESSION[$this->cookie_prefix . '_password'] = $data['password'];
            }
        }

        return true;
    }

    public function update_details()
    {
        global $cms;

        $cms->set_section($this->table, $this->user['id']);

        $errors = $cms->validate();

        if ($errors) {
            $this->show_error('Check the following fields:\n' . implode("\n", $errors));
            return false;
        } elseif ($_POST['validate']) {
            print 1;
            exit;
        }

        if ($_POST['password']) {
            if ($this->hash_password) {
                $_POST['password'] = $this->create_hash($_POST['password']);
            }
        } else {
            unset($_POST['password']);
        }

        $id = $cms->save();

        //update session email and password if neccessary
        if ($_POST['email']) {
            $_SESSION[$this->cookie_prefix . '_email'] = $_POST['email'];
        }

        if ($_POST['password']) {
            $_SESSION[$this->cookie_prefix . '_password'] = $_POST['password'];
        }

        $row = sql_query('SELECT * FROM ' . $this->table . "
			WHERE
				id='" . escape($id) . "'
		", 1);

        $this->user = $row;

        $this->update_success = true;
    }

    public function do_forgot($email) //invoked by $_POST['forgot_password']
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
        }
        
        if (!$password) {
            return 'missing password';
        }
        
        //check code
        $user = sql_query("SELECT id FROM users
			WHERE
				code = '" . escape($code) . "' AND
				code_expire > CURDATE()
			LIMIT 1
		", 1);
    
        if ($user) {
            //hash password
            $hash = $this->create_hash($password);

            // save user
            sql_query("UPDATE users SET
				password = '" . escape($hash) . "'
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
        if (!$email) {
            $email = $_POST['email'];
        }

        if (!is_email($email)) {
            $errors[] = 'email';

            print json_encode($errors);
            exit;
        }

        $row = sql_query('SELECT * FROM ' . $this->table . "
			WHERE
				email = '" . escape($email) . "'
		", 1);

        if ($row) {
            $user = $row;

            if ($_POST['validate']) {
                print 1;
                exit;
            }
        } else {
            $this->errors[] = 'email not recognised';
            $this->show_error('Email address is not in use.');

            //redirect('forgot');
            return false;
        }

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
            $reps['link'] = 'http://' . $_SERVER['HTTP_HOST'] . '/forgot?user=' . $user['id'] . '&code=' . $code;
        } else {
            if (!$user['password']) {
                $password = generate_password();

                sql_query('UPDATE ' . $this->table . " SET
						password = '" . addslashes($password) . "'
					WHERE
						id = '" . $user['id'] . "'
					LIMIT 1
				");

                $user['password'] = $password;
            }

            $reps = $user;
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

            $password_hash = $this->create_hash($password);

            $row = sql_query('SELECT * FROM ' . $this->table . "
				WHERE
					email='" . escape($email) . "' AND
					password='" . escape($password_hash) . "'
			", 1);

            if ($row) {
                $this->user = $row;
                $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
                $_SESSION[$this->cookie_prefix . '_email'] = $email;
                $_SESSION[$this->cookie_prefix . '_password'] = $password_hash;
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
        if ($_POST['email'] and $_POST['password']) {
            $this->check_login_attempts();

            $password = $_POST['password'];
            if ($this->hash_password) {
                $password = $this->create_hash($_POST['password']);
            }

            $row = sql_query('SELECT * FROM ' . $this->table . "
				WHERE
					email='" . escape($_POST['email']) . "' AND
					password='" . escape($password) . "'
					" . ($this->login_wherestr ? 'AND ' . $this->login_wherestr : '') . '
			', 1);

            if ($row) {
                $this->user = $row;
                $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
                
                $_SESSION[$this->cookie_prefix . '_email'] = $_POST['email'];
                $_SESSION[$this->cookie_prefix . '_password'] = $password;
                $_SESSION[$this->cookie_prefix . '_expires'] = time() + $this->expiry;

                if ($this->log_last_login) {
                    sql_query('UPDATE ' . $this->table . " SET
						last_login=NOW()
						WHERE
							email='" . escape($_POST['email']) . "'
						LIMIT 1
					");
                }
                
                // load cms privileges
                $this->load();

                //cookies
                if ($_POST['remember']) {
                    setcookie($this->cookie_prefix . '_email', $_POST['email'], time() + (86400 * $this->cookie_duration), '/', $this->cookie_domain);
                    setcookie($this->cookie_prefix . '_password', md5($this->secret_phrase . $password), time() + (86400 * $this->cookie_duration), '/', $this->cookie_domain);
                }

                if ($_POST['validate']) {
                    print 1;
                    exit;
                }

                if ($_SESSION['request']) {
                    $request = $_SESSION['request'];
                    unset($_SESSION['request']);
                    redirect($request);
                }
            } else {
                $this->errors[] = 'password incorrect';

                $this->show_error('login incorrect');

                $this->failed_login_attempt($_POST['email'], $_POST['password']);
            }
        } else {
            $this->errors[] = 'email';
            $this->errors[] = 'password';

            $this->show_error('missing email or password');
        }

        if (count($this->errors)) {
            print json_encode($this->errors);
            exit;
        } elseif ($_POST['validate']) {
            print 1;
            exit;
        }
    }

    public function check_login()
    {
        if ($this->activation_required and $this->user and !$this->user['active']) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('/activate');
        }

        if (!$this->user) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect($this->login);
        }
    }

    public function check_admin()
    {
        global $vars, $cms;

        if (!table_exists($this->table)) {
            $cms->check_table($this->table, $vars['fields'][$this->table]);
            sql_query('ALTER TABLE `users` ADD UNIQUE `email` ( `email` )');
        }

        $row = sql_query('SELECT * FROM ' . $this->table . ' WHERE admin>0 LIMIT 1', 1);
        if (!$row) {
            $default_pass = '123';
            if ($this->hash_password) {
                $default_pass = $this->create_hash($default_pass);
            }
            
            sql_query('INSERT INTO ' . $this->table . " SET email='admin', password='" . $default_pass . "', admin='1'");
        }

        if (!$this->user['admin']) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('?option=login');
        }
    }

    public function do_logout()
    {
        $_SESSION = [];

        setcookie($this->cookie_prefix . '_email', '', time() - (86400 * $this->cookie_duration), '/', $this->cookie_domain);
        setcookie($this->cookie_prefix . '_password', '', time() - (86400 * $this->cookie_duration), '/', $this->cookie_domain);

        setcookie($this->cookie_prefix . '_email', '', time() - (86400 * $this->cookie_duration), '/');
        setcookie($this->cookie_prefix . '_password', '', time() - (86400 * $this->cookie_duration), '/');

        setcookie($this->cookie_prefix . '_email', '', time() - (86400 * $this->cookie_duration), '/', str_replace('www', '', $this->cookie_domain));
        setcookie($this->cookie_prefix . '_password', '', time() - (86400 * $this->cookie_duration), '/', str_replace('www', '', $this->cookie_domain));

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

        session_destroy();
    }

    public function logout()
    {
        $this->do_logout();
        redirect('/');
    }

	/*
    public function trigger_event($event)
    {
        global $auth_config;

        $args = func_get_args();

        if (is_array($auth_config['handlers'])) {
            foreach ($auth_config['handlers'] as $handler) {
                if ($handler['event'] == $event) {
                    $handler['handler']($args[1]);
                }
            }
        }
    }

    public function get_formkey()
    {
        $token = dechex($this->user['id']) . '.' . dechex(mt_rand());
        $hash = sha1($this->form_secret . '-' . $token);
        return htmlspecialchars($token . '-' . $hash);
    }

    public function check_formkey($formkey)
    {
        $parts = explode('-', $formkey);

        if (2 == count($parts)) {
            list($token, $hash) = $parts;

            $arr = explode('.', $token);
            $userid = hexdec($arr[0]);

            if ($userid == $this->user['id'] and $hash == sha1($this->form_secret . '-' . $token)) {
                return true;
            }
        }

        return false;
    }
    */
}
