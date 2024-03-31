<?php
session_start();

/**
* @author  Adam Jimenez
* Class auth
*/
class auth
{
    /**
    * @var bool
    */
    public $initiated = false;

    /**
    * @var bool
    */
    public $generate_password = true;

    /**
    * @var int
    */
    public $cookie_duration = 14;

    /**
    * @var bool
    */
    public $hash_password = false;

    /**
    * @var string
    */
    public $hash_salt = 'a9u03udk[';

    /**
    * @var array
    */
    public $errors = [];

    /**
    * @var int
    */
    public $expiry = 60;

    /**
    * timeout in hours
    * @var int
    */
    public $activation_timeout = 24;

    /**
    * email activation
    * @var bool
    */
    public $email_activation = false;

    /**
    * Use a secret term to encrypt cookies
    * @var string
    */
    public $secret_phrase = 'asdagre3';

    /**
    * for use with session and cookie vars
    *
    * @var string
    */
    public $cookie_prefix = 'site';

    /**
    * additional params to check when logging in
    *
    * @var string
    */
    public $login_params = [];

    /**
    * @var string
    */
    public $table = 'users';

    /**
    * @var string
    */
    public $cookie_domain;

    public $required;

    public $user;

    public $check_login_attempts;

    /**
    * for use with single sign on
    *
    * @var string
    */
    public $facebook_appId;

    /**
    * for use with single sign on
    *
    * @var string
    */
    public $facebook_secret;

    /**
    * for use with single sign on
    *
    * @var string
    */
    public $google_appId;

    /**
    * for use with single sign on
    *
    * @var string
    */
    public $google_secret;

    /**
    * auth constructor.
    * @param array $config
    * @throws Exception
    */
    public function __construct() {
        $this->cookie_secure = ($_SERVER['HTTPS'] === 'on');
        $cookie_params = session_get_cookie_params();
        
        if (PHP_VERSION_ID < 70300) {
            session_set_cookie_params($cookie_params['lifetime'], '/; SameSite=None', $cookie_params['domain'], $this->cookie_secure, $cookie_params['httponly']);
        } else {
            session_set_cookie_params([
                'lifetime' => $cookie_params['lifetime'],
                'path' => '/',
                'domain' => $cookie_params['domain'],
                'secure' => $this->cookie_secure,
                'httponly' => $cookie_params['httponly'],
                'samesite' => 'None'
            ]);
        }
        
        $this->cookie_domain = $_SERVER['HTTP_HOST'];
    }

    /**
    * @return bool
    */
    public function shouldHashPassword(): bool
    {
        return $this->hash_password;
    }

    public function init(?array $config = []) {
        global $vars,
        $email_templates;

        foreach ($config as $k => $v) {
            if ('' !== $v) {
                $this->$k = $v;
            }
        }

        if (!$email_templates['Password Reminder']) {
            $email_templates['Password Reminder'] = '<p>Hi {$name},<br>
            You have requested a password reset for your {$domain} member account.<br>
            <br>
            Please use this <a href="{$link}">link</a> to login.<br>
            <br>
            Kind regards<br>
            The {$domain} Team<p>';

            $email_templates['Password Reminder'] = str_replace("\t", '', $email_templates['Password Reminder']);
        }

        if (!$email_templates['Registration Confirmation']) {
            $email_templates['Registration Confirmation'] = '<p>Hi {$name},<br>
            Thank you for registering as a member of {$domain}.<br>
            To login to your new account, visit: {$link}<br>
            Kind regards<br>
            The {$domain} Team</p>';

            $email_templates['Registration Confirmation'] = str_replace("\t", '', $email_templates['Registration Confirmation']);
        }

        if (!$email_templates['Your two-factor sign in code']) {
            $email_templates['Your two-factor sign in code'] = '<p>Hi {$name},<br>
            Your two-factor sign in code<br>
            {$code}<br>
            Kind regards<br>
            The {$domain} Team</p>';

            $email_templates['Your two-factor sign in code'] = str_replace("\t", '', $email_templates['Your two-factor sign in code']);
        }

        $this->required = $vars['required'][$this->table];

        if (true === $this->initiated) {
            return false;
        }

        //check for cookies
        if (!$_SESSION[$this->cookie_prefix . '_user']) {
            $email = '';
            $password = '';

            if ($_COOKIE[$this->cookie_prefix . '_email'] && $_COOKIE[$this->cookie_prefix . '_password']) {
                $email = $_COOKIE[$this->cookie_prefix . '_email'];
                $password = $_COOKIE[$this->cookie_prefix . '_password'];
            }

            $login_str = $this->get_login_str();

            if ($email && $password && table_exists($this->table)) {
                $result = sql_query('SELECT * FROM ' . $this->table . "
                    WHERE
                        email='" . escape($email) . "'
                        " . $this->login_str, 1);

                if ($result && $password == md5($this->secret_phrase . $result['password'])) {
                    // renew cookie
                    $this->set_login($result['email'], $result['password']);
                }
            }
        }

        //check if logged in
        if ($_SESSION[$this->cookie_prefix . '_user'] and time() < $_SESSION[$this->cookie_prefix . '_expires']) {
            $this->user = $_SESSION[$this->cookie_prefix . '_user'];
        } elseif ($_SESSION[$this->cookie_prefix . '_email'] and $_SESSION[$this->cookie_prefix . '_password'] and table_exists($this->table)) {
            $this->load();
        }

        if ($_GET['u']) {
            $_SESSION['request'] = $_GET['u'];
        }

        $this->initiated = true;

        if ($config['callback']) {
            $config['callback']($this);
        }
    }

    private function get_login_str() {
        $where_str = '';

        if (count($this->login_params)) {
            $parts = [];
            foreach ($this->login_params as $k => $v) {
                $parts[] = escape($k) . ' = ' . escape($v);
            }
            $where_str = ' AND ' . implode(' AND ', $parts);
        }

        return $where_str;
    }

    public function single_sign_on() {
        if (false === class_exists('Hybridauth\Hybridauth')) {
            return;
        }

        $result = [];

        $config = [
            'callback' => 'https://' . $_SERVER['HTTP_HOST'] . '/login?action=auth',

            'providers' => [
                'Facebook' => [
                    'enabled' => true,
                    'keys' => ['id' => $this->facebook_appId,
                        'secret' => $this->facebook_secret],
                    'trustForwarded' => true,
                    'scope' => 'email',
                ],
                'Google' => [
                    'enabled' => true,
                    'keys' => ['id' => $this->google_appId,
                        'secret' => $this->google_secret],
                    'scope' => 'profile email',
                ],
                "Apple" => [
                    "enabled" => true,
                    "keys" => [
                        "id" => $this->apple_appId,
                        "team_id" => $this->apple_teamId,
                        "key_id" => $this->apple_keyId,
                        "key_content" => $this->apple_key,
                    ],
                    "scope" => "name email",
                    "verifyTokenSignature" => true
                ]
            ]
        ];

        // the selected provider
        $provider_name = $_GET['provider'] ?: $_SESSION['provider'];
        $provider_name = $_GET['hauth_start'] ?: $provider_name;
        $provider_name = $_GET['hauth_done'] ?: $provider_name;
        $provider_name = (string)$provider_name;

        // initialize Hybrid_Auth with a given file
        $hybridauth = new Hybridauth\Hybridauth($config);

        // single sign on, triggerd by $_GET['provider'] = google
        if (isset($_GET['provider']) || $_GET['action'] === 'auth') {
            $_SESSION['provider'] = $provider_name;

            if ($_GET['u']) {
                $_SESSION['request'] = $_GET['u'];
            }

            $adapter = $hybridauth->getAdapter($provider_name);

            // check if we have the token from native app
            if ($_GET['idToken']) {
                $user_profile = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $_GET['idToken'])[1]))));
            } else {
                if ($_GET['accessToken']) {
                    $adapter->setAccessToken(['access_token' => $_GET['accessToken']]);
                } else {
                    // try to authenticate with the selected provider
                    $adapter = $hybridauth->authenticate($provider_name);
                }

                // then grab the user profile
                $user_profile = $adapter->getUserProfile();
            }

            $email = $user_profile->email or die('missing email');

            $result = $this->verified_login($email, $user_profile->firstName, $user_profile->lastName);
        }

        return $result;
    }

    public function verified_login($email, $name, $surname) {
        // find user
        $login_str = $this->get_login_str();

        $user = sql_query('SELECT * FROM ' . $this->table . "
            WHERE
                email='" . escape($email) . "'
                " . $login_str . "
            LIMIT 1
        ", 1);

        // create user if they don't exist
        if (!$user) {
            $user['password'] = generate_password();

            sql_query('INSERT INTO ' . $this->table . " SET
                name='" . escape($name) . "',
                surname='" . escape($surname) . "',
                email='" . escape($email) . "',
                password='" . escape($user['password']) . "'
            ");

            $result = [
                'code' => 2,
                'message' => 'Registration success',
            ];
        } else {
            $result = [
                'code' => 1
            ];
        }

        // log in
        $this->set_login($email, $user['password']);
        $this->load();

        return $result;
    }

    public function load() {
        if (!$_SESSION[$this->cookie_prefix . '_email'] || !$_SESSION[$this->cookie_prefix . '_password']) {
            return false;
        }

        $login_str = $this->get_login_str();

        $result = sql_query('SELECT * FROM ' . $this->table . "
            WHERE
                email='" . escape($_SESSION[$this->cookie_prefix . '_email']) . "' AND
                password='" . escape($_SESSION[$this->cookie_prefix . '_password']) . "'
                " . $this->login_str, 1);

        if ($result) {
            $this->user = $result;
            $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
            $_SESSION[$this->cookie_prefix . '_expires'] = time() + $this->expiry;
        } else {
            $this->failed_login_attempt($_SESSION[$this->cookie_prefix . '_email'], $_SESSION[$this->cookie_prefix . '_password']);
            $this->logout();
        }
    }

    /**
    * @param string $password
    * @return string
    */
    public function create_hash(string $password): string
    {
        return $this->hash_password ? hash('sha256', $this->hash_salt . $password) : $password;
    }

    /**
    * @param string $email
    * @param string $pass password hash
    */
    public function set_login(string $email, string $pass) {
        $_SESSION[$this->cookie_prefix . '_email'] = $email;
        $_SESSION[$this->cookie_prefix . '_password'] = $pass;

        $this->set_cookie($this->cookie_prefix . '_email', $email);
        $this->set_cookie($this->cookie_prefix . '_password', md5($this->secret_phrase . $pass));
    }
    
    public function set_cookie($key, $value) {
        $arr_cookie_options = array (
            'expires' => time() + 86400 * $this->cookie_duration, 
            'path' => '/', 
            'domain' => $this->cookie_domain, // leading dot for compatibility or use subdomain
            'secure' => $this->cookie_secure,     // or false
            'httponly' => true,    // or false
            'samesite' => 'None' // None || Lax  || Strict
        );
        setcookie($key, $value, $arr_cookie_options); 
    }
    
    public function unset_cookie($key) {
        $arr_cookie_options = array (
            'expires' => time() - 86400 * $this->cookie_duration, 
            'path' => '/', 
            'domain' => $this->cookie_domain, // leading dot for compatibility or use subdomain
            'secure' => $this->cookie_secure,     // or false
            'httponly' => true,    // or false
            'samesite' => 'None' // None || Lax  || Strict
        );
        setcookie($key, '', $arr_cookie_options); 
    }

    /**
    * @param string $email
    * @throws Exception
    * @return bool
    */
    public function failed_login_attempt(string $email = '') {
        if (false === $this->check_login_attempts or !table_exists('cms_login_attempts')) {
            return false;
        }

        sql_query("INSERT INTO cms_login_attempts SET
            email='" . escape($email) . "',
            ip='" . escape($_SERVER['REMOTE_ADDR']) . "'
        ");
    }

    public function check_login_attempts() {
        if (false === $this->check_login_attempts or !table_exists('cms_login_attempts')) {
            return false;
        }

        sql_query('DELETE FROM cms_login_attempts
            WHERE
                `date`<DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ');

        $rows = sql_query("SELECT id FROM cms_login_attempts
            WHERE
                ip='" . escape($_SERVER['REMOTE_ADDR']) . "' AND
                `date`>DATE_SUB(NOW(),INTERVAL 10 MINUTE)
        ");

        if (count($rows) >= 5) {
            $this->show_error(['password Too many login attempts - try again in 10 minutes.']);
        }
    }

    public function show_error($errors) {
        print json_encode([
            'success' => false,
            'error' => current($errors),
            'errors' => $errors
        ]);
        exit;
    }

    public function register($options = []) //invoked by $_POST['register']
    {
        global $cms,
        $request,
        $sections,
        $vars;

        if (isset($options['fields'])) {
            $options['fields'] = ['email'];
        }

        $result = [];
        $data = $_POST;

        //activation
        if ($_GET['code']) {
            //check code
            $user = sql_query("SELECT user FROM cms_activation
                WHERE
                    code = '" . escape($_GET['code']) . "' AND
                    user = '" . escape($_GET['user']) . "' AND
                    expiration > CURDATE()
                LIMIT 1
            ", 1);

            if ($user) {
                // save user
                sql_query('UPDATE ' . $this->table . " SET
                    email_verified = 1
                    WHERE
                        id='" . escape($user['user']) . "'
                    LIMIT 1
                ");

                $this->load();
            }

            return [
                'code' => 3,
                'message' => 'Thanks for verifying your email address',
            ];
        } elseif ($data['register']) {
            $cms->set_section($this->table, $options['fields']);

            unset($data['admin']);

            $errors = $cms->validate($_POST, $options['recaptcha']);

            if (isset($data['confirm']) && ($data['confirm'] !== $data['password'])) {
                $errors[] = 'password passwords do not match';
            }

            if ($errors) {
                $this->show_error($errors);
            } elseif ($_POST['validate']) {
                print 1;
                exit;
            }

            if ($options['recaptchav3']) {
                if (!$cms->verifyRecaptcha($_POST['g-recaptcha-response'])) {
                    die('feiled captcha');
                }
            }

            $data['password'] = $data['password'] ?: generate_password();

            $id = $cms->save($data);

            $reps = $data;

            $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/';

            $fields = $cms->get_fields('users');

            if ($fields['email_verified']) {
                //activation code
                $code = substr(md5(rand(0, 10000)), 0, 10);

                if (table_exists('cms_activation')) {
                    sql_query("INSERT INTO cms_activation SET
                        code = '" . escape($code) . "',
                        expiration = DATE_ADD(CURDATE(), INTERVAL " . $this->activation_timeout . " HOUR),
                        user = " . $id . "
                        ON DUPLICATE KEY UPDATE
                            code = '" . escape($code) . "',
                            expiration = DATE_ADD(CURDATE(), INTERVAL " . $this->activation_timeout . " HOUR)
                    ");
                }

                $reps['link'] .= '?user=' . $id . '&code=' . $code;
            }

            $reps['domain'] = $_SERVER['HTTP_HOST'];

            email_template($_POST['email'], 'Registration Confirmation', $reps);

            // login
            $data['password'] = $this->create_hash($data['password']);

            $this->set_login($_POST['email'], $data['password']);
        }

        // check status
        $this->load();

        if ($this->user) {
            if ($this->email_activation && !$this->user['email_verified']) {
                $result = [
                    'code' => 2,
                    'message' => 'Activation required, please check your email',
                ];
            } else {
                $result = [
                    'code' => 1,
                    'message' => 'Registration success',
                ];
            }
        }

        return $result;
    }
    
    // cms submit wrapper for forgot password
    public function forgot_password() {
        global $request;
        
        try {
        	$result = $this->forgot_password_handler($_REQUEST, [
        		'request' => $request
        	]);
        	
        	if ($_POST['validate']) {
        		print 1;
            	exit;
        	}
        } catch(Exception $e) {
            $response = [
            	'errors' => [$e->getMessage()]
        	];
            print json_encode($response);
            exit;
        }
        
        return $result;
    }

    /**
    * @throws Exception
    * @return array
    */
    public function forgot_password_handler($data = null, $options = []) {
        global $vars, $cms, $auth;

        $result = [];
        
        if (!$data) {
            $data = $_POST;
        }

        if ($auth->user) {
            $result = [
                'code' => 1,
                'message' => 'Logged in',
            ];
        } else if ($data['code']) {
            //check code
            $cms_activation = sql_query("SELECT id, user FROM cms_activation
                WHERE
                    code = '" . escape($data['code']) . "' AND
                    user = '" . escape($data['user']) . "' AND
                    expiration > CURDATE()
                LIMIT 1
            ", 1);
            
            if (!$cms_activation) {
                throw new Exception('invalid');
            }

            if ($data['password']) {
                //check fields are completed
                if ($data['password'] && strlen($data['password']) < 6) {
                    throw new Exception('password min 6 characters');
                }

                // trigger error
                if ($data['validate']) {
                    return true;
                }

                //hash password
                $hash = $this->create_hash($data['password']);

                // save user
                sql_query("UPDATE " . $this->table . " SET
                    password = '" . escape($hash) . "'
                    WHERE
                        id='" . escape($cms_activation['user']) . "'
                    LIMIT 1
                ");

                $fields = $cms->get_fields('users');
                if ($fields['email_verified']) {
                    sql_query("UPDATE " . $this->table . " SET
                        email_verified = 1
                        WHERE
                            id='" . escape($cms_activation['user']) . "'
                        LIMIT 1
                    ");
                }

                // send email confirmation
                $user = sql_query('SELECT id, email FROM ' . $this->table . "
                    WHERE
                        id='" . escape($cms_activation['user']) . "'
                ", 1);

                if ($user) {
                    // auto login
                    $this->set_login($user['email'], $hash);
                    $this->load();

                    $email_template = $options['password_changed_email'] ?: 'Password Changed';

                    try {
                        email_template($user['email'], $email_template);
                    } catch (Exception $e) {}

                    sql_query("DELETE FROM cms_activation WHERE id = '" . (int)$cms_activation['id'] . "'");
                }

                $result = [
                    'code' => 1,
                    'message' => 'New password has been set',
                ];

                $cms->save_log('users', $user['id'], 'login', 'Password reset from ' . $_SERVER['REMOTE_ADDR']);
            } else {
                $result = [
                    'code' => 3,
                    'message' => 'Enter your new password',
                ];
            }
        } elseif ($data['forgot_password']) {
            // default to post value
            if (true === empty($data['email']) || false === is_email($data['email'])) {
                throw new Exception('email');
            }

            // check user exists
            $user = sql_query('SELECT id FROM ' . $this->table . "
                WHERE
                    email = '" . escape($data['email']) . "'
            ", 1);

            if ($data['validate']) {
                return true;
            }

            if ($user) {
                $this->send_password_reset($data['email'], $options['request']);
            }

            $result = [
                'code' => 2,
                'message' => 'Password recovery email sent if account exists',
            ];
        }

        return $result;
    }

    public function send_password_reset($email, $request = '') {
        $user = sql_query('SELECT id FROM ' . $this->table . "
            WHERE
                email = '" . escape($email) . "'
        ", 1);

        if (!$user) {
            return false;
        }

        $reps = $user;

        //activation code
        $code = substr(md5(rand(0, 10000)), 0, 10);

        sql_query("INSERT INTO cms_activation SET
            code = '" . escape($code) . "',
            expiration = DATE_ADD(CURDATE(), INTERVAL " . $this->activation_timeout . " HOUR),
            user = " . $user['id'] . "
            ON DUPLICATE KEY UPDATE
                code = '" . escape($code) . "',
                expiration = DATE_ADD(CURDATE(), INTERVAL " . $this->activation_timeout . " HOUR)
        ");

        $reps['user_id'] = $user['id'];
        $reps['code'] = $code;
        $reps['domain'] = $_SERVER['HTTP_HOST'];
        $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $request . '?user=' . $user['id'] . '&code=' . $code;

        $email_template = $options['password_reminder_email'] ?: 'Password Reminder';

        email_template($email, $email_template, $reps);
    }

    public function login($data = null) {
        global $cms;

        $result = $this->single_sign_on();

        if ($result['code']) {
            return $result;
        }

        if (!$data) {
            $data = $_POST;
        }

        $result = [];

        if ($this->user) {
            $result['code'] = 1;
        } elseif ($data['login']) {
            $error = '';
            if ($data['email'] && $data['password']) {
                $this->check_login_attempts();

                $data['password'] = $this->create_hash($data['password']);

                $login_str = $this->get_login_str();

                $row = sql_query('SELECT * FROM ' . $this->table . "
                    WHERE
                        email='" . escape($data['email']) . "'
                        " . $login_str, 1);

                if ($row) {
                    if ($row['password'] === $data['password']) {
                        $this->user = $row;
                        $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
                        $_SESSION[$this->cookie_prefix . '_expires'] = time() + $this->expiry;

                        $this->set_login($data['email'], $data['password']);

                        $result['code'] = 1;
                        $result['message'] = 'User logged in';

                        $cms->save_log('users', $row['id'], 'login', 'Successful login from ' . $_SERVER['REMOTE_ADDR']);
                    } else {
                        $error = 'password incorrect';
                        $this->failed_login_attempt($data['email'], $data['password']);

                        $cms->save_log('users', $row['id'], 'login', 'Failed login from ' . $_SERVER['REMOTE_ADDR']);
                    }
                } else {
                    $error = 'password incorrect';
                }
            } elseif (!$data['email']) {
                $error = 'email required';
            } elseif (!$data['password']) {
                $error = 'password required';
            }

            if ($error) {
                //$this->show_error($errors);
                $result['error'] = $error;
            } elseif ($data['validate']) {
                print 1;
                exit;
            }
        }

        return $result;
    }

    // force user to log in
    public function check_login(): void
    {
        if ($this->email_activation && $this->user && !$this->user['email_verified']) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('/register');
        }

        if (!$this->user) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
        }
    }

    public function check_admin() {
        if (!$this->user['admin']) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('/admin?option=login');
        }
    }

    /**
    * @param bool $redirect
    */
    public function logout(bool $redirect = true) {
        // clear 2fa
        $trusted = $this->check_2fa();
        if ($trusted) {
            sql_query("DELETE FROM cms_trusted_devices WHERE
                id = " . (int)$trusted['id'] . "
            ");
        }
        
        // unset cookies
        $this->unset_cookie($this->cookie_prefix . '_email');
        $this->unset_cookie($this->cookie_prefix . '_password');
        $this->unset_cookie($this->cookie_prefix . '_2fa');

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

        return true;
    }

    function get_formkey() {
        $token = dechex($this->user['id']).'.'.dechex(mt_rand());
        $hash = sha1($this->form_secret.'-'.$token);
        return htmlspecialchars($token.'-'.$hash);
    }

    function check_formkey($formkey) {
        $parts = explode('-', $formkey);

        if (count($parts) == 2) {
            list($token, $hash) = $parts;

            $arr = explode('.', $token);
            $userid = hexdec($arr[0]);

            if ($userid == $this->user['id'] and $hash == sha1($this->form_secret.'-'.$token)) {
                return true;
            }
        }

        return false;
    }
    
    function pass_2fa() {
        unset($_SESSION['otp']);
        unset($_SESSION['otp_attempts']);
    
        $hash = md5(generate_password(16));
    
        // save trusted device
        sql_query("INSERT INTO cms_trusted_devices SET
            user = '".escape($this->user['id'])."',
            useragent = '".escape($_SERVER['HTTP_USER_AGENT'])."',
            hash = '".escape($hash)."',
            ip = '".escape($_SERVER['REMOTE_ADDR'])."'
        ");
    
        // set cookie
        $this->set_cookie($this->cookie_prefix . '_2fa', $hash);
        return true;
    }
    
    function check_2fa() {
        if ($this->user['email'] === 'admin' || !table_exists('cms_trusted_devices') || !$this->from_email) {
            return null;
        }
        
        $trusted = false;
        if ($_COOKIE[$this->cookie_prefix . '_2fa']) {
            $trusted = sql_query("SELECT id FROM cms_trusted_devices
                WHERE
                    user = '".(int)$this->user['id']."' AND
                    hash = '".escape($_COOKIE[$this->cookie_prefix . '_2fa'])."'
                LIMIT 1
            ", 1);
        }
        
        return $trusted;
    }
    
    function send_2fa($otp_length = 4) {
        if ($_SESSION['otp']) {
            return false;
        }
        
        $_SESSION['otp'] = strtoupper(generate_password($otp_length));
        $_SESSION['otp_attempts'] = 0;

        // email it
        email_template($this->user['email'], 'Your two-factor sign in code', [
            'name' => $this->user['name'],
            'code' => $_SESSION['otp'],
            'domain' => $_SERVER['HTTP_HOST'],
        ]);
        
        return true;
    }
}