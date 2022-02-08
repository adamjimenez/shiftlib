<?php
global $cookie_secure;

$cookie_secure = ($_SERVER['HTTPS'] === 'on');
$cookie_params = session_get_cookie_params();

if(PHP_VERSION_ID < 70300) {
    session_set_cookie_params($cookie_params['lifetime'], '/; SameSite=Lax', $cookie_params['domain'], $cookie_secure, $cookie_params['httponly']);
} else {
    session_set_cookie_params([
        'lifetime' => $cookie_params['lifetime'],
        'path' => '/',
        'domain' => $cookie_params['domain'],
        'secure' => $cookie_secure,
        'httponly' => $cookie_params['httponly'],
        //'samesite' => 'None'
    ]);
}

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

    public $log_last_login;

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
    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public function shouldHashPassword(): bool
    {
        return $this->hash_password;
    }

    public function init(?array $config = [])
    {
        global $vars, $email_templates;

        $this->cookie_domain = $_SERVER['HTTP_HOST'];

        foreach ($config as $k => $v) {
            if ('' !== $v) {
                $this->$k = $v;
            }
        }

        if (!$email_templates['Password Reminder']) {
            $email_templates['Password Reminder'] = 'Dear {$name},
        
            You have requested a password reset for your {$domain} member account.
            Please use the following link:
        
            {$link}
        
            Kind regards
        
            The {$domain} Team';
        }

        if (!$email_templates['Registration Confirmation']) {
            $email_templates['Registration Confirmation'] = 'Dear {$name},
        
            Thank you for registering as a member of {$domain}.
        
            To login to your new account, visit: {$link}
        
            Kind regards
            The {$domain} Team';
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
                        " . $this->login_str
                , 1);
    
                if ($result && $password == md5($this->secret_phrase . $result['password'])) {
                    $_SESSION[$this->cookie_prefix . '_email'] = $result['email'];
                    $_SESSION[$this->cookie_prefix . '_password'] = $result['password'];
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
    }
    
    private function get_login_str() 
    {
        $where_str = '';
        
        if (count($this->login_params)) {
            $parts = [];
            foreach($this->login_params as $k => $v) {
                $parts[] = escape($k) . ' = ' . escape($v);
            }
            $where_str = ' AND ' . implode(' AND ', $parts);
        }
        
        return $where_str;
    }
    
    public function single_sign_on()
    {
        if (false === class_exists('Hybridauth\Hybridauth')) {
            return;
        }
        
        $result = [];
        
        $config = [
            'callback' => 'https://' . $_SERVER['HTTP_HOST'] . '/login?action=auth',
    
            'providers' => [
                'Facebook' => [
                    'enabled' => true,
                    'keys' => [ 'id' => $this->facebook_appId, 'secret' => $this->facebook_secret ],
                     'trustForwarded' => true,
                      'scope' => 'email',
                ],
                'Google' => [
                    'enabled' => true,
                    'keys' => [ 'id' => $this->google_appId, 'secret' => $this->google_secret ],
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
    
        // initialize Hybrid_Auth with a given file
        $hybridauth = new Hybridauth\Hybridauth($config);
        
        // single sign on, triggerd by $_GET['provider'] = google
        if (isset($_GET['provider']) || $_GET['action'] === 'auth') {
            $_SESSION['provider'] = $provider_name;
        
            if ($_GET['u']) {
                $_SESSION['request'] = $_GET['u'];
            }
            
            // try to authenticate with the selected provider
            $adapter = $hybridauth->authenticate($_SESSION['provider']);
            
            // then grab the user profile
            $user_profile = $adapter->getUserProfile();
        
            $email = $user_profile->email or die('missing email');
            
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
                    name='" . escape($user_profile->firstName) . "',
                    surname='" . escape($user_profile->lastName) . "',
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
        }
        
        return $result;
    }

    public function load()
    {
        if (!$_SESSION[$this->cookie_prefix . '_email'] || !$_SESSION[$this->cookie_prefix . '_password']) {
            return false;
        }
        
        $login_str = $this->get_login_str();
        
        $result = sql_query('SELECT * FROM ' . $this->table . " 
            WHERE
                email='" . escape($_SESSION[$this->cookie_prefix . '_email']) . "' AND
                password='" . escape($_SESSION[$this->cookie_prefix . '_password']) . "'
                " . $this->login_str
        , 1);

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
    public function set_login(string $email, string $pass)
    {
        global $cookie_secure;
        
        $_SESSION[$this->cookie_prefix . '_email'] = $email;
        $_SESSION[$this->cookie_prefix . '_password'] = $pass;
        
        setcookie($this->cookie_prefix . '_email', $email, time() + (86400 * $this->cookie_duration), '/', $this->cookie_domain, true);
        setcookie($this->cookie_prefix . '_password', md5($this->secret_phrase . $pass), time() + (86400 * $this->cookie_duration), '/', $this->cookie_domain, true);
    }

    /**
     * @param string $email
     * @throws Exception
     * @return bool
     */
    public function failed_login_attempt(string $email = '')
    {
        if (false === $this->check_login_attempts or !table_exists('cms_login_attempts')) {
            return false;
        }

        sql_query("INSERT INTO cms_login_attempts SET
            email='" . escape($email) . "',
            ip='" . escape($_SERVER['REMOTE_ADDR']) . "'
        ");
    }

    public function check_login_attempts()
    {
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

    public function show_error($errors)
    {
        print json_encode([
            'success' => false,
            'error' => current($errors),
            'errors' => $errors
        ]);
        exit;
    }

    public function register($options = []) //invoked by $_POST['register']
    {
        global $cms, $request, $sections, $vars;
        
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
    
            $data['password'] = $data['password'] ?: generate_password();
    
            $id = $cms->save($data);
    
            $reps = $data;
    
            $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $request;
            
            if ($vars["fields"]["users"]['email_verified']) {
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
                
                $reps['link'] .=  '?user=' . $id . '&code=' . $code;
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

    /**
     * @throws Exception
     * @return array
     */
    public function forgot_password()
    {
        global $request, $vars;
        
        $result = [];
        $data = $_POST;
        
        if ($_GET['code']) {
            //check code
            $user = sql_query("SELECT user FROM cms_activation
                WHERE
                    code = '" . escape($_GET['code']) . "' AND
                    user = '" . escape($_GET['user']) . "' AND
                    expiration > CURDATE()
                LIMIT 1
            ", 1);
        
            if ($user and isset($data['reset_password'])) {
                //check fields are completed
                $errors = [];
        
                if (!$data['password']) {
                    $errors[] = 'password';
                }
                if ($data['password'] && strlen($data['password']) < 6) {
                    $errors[] = 'password min 6 characters';
                }
        
                //else trigger error
                if (count($errors)) {
                    print json_encode($errors);
                    exit;
                } elseif ($data['validate']) {
                    print 1;
                    exit;
                }
                
                //hash password
                $hash = $this->create_hash($data['password']);
        
                // save user
                sql_query("UPDATE users SET
                    password = '" . escape($hash) . "'
                    WHERE
                        id='" . escape($user['user']) . "'
                    LIMIT 1
                ");
                
                if ($vars["fields"]["users"]['email_verified']) {
                    sql_query("UPDATE users SET
                        email_verified = 1
                        WHERE
                            id='" . escape($user['user']) . "'
                        LIMIT 1
                    ");
                }
                
                $result = [
                    'code' => 3,
                    'message' => 'New password has been set, <a href="/login">log in</a>',
                ];
            } elseif ($user) {
                $result = [
                    'code' => 2,
                    'message' => 'Enter your new password',
                ];
            } else {
                $result = [
                    'code' => 4,
                    'message' => 'Code is invalid or expired',
                ];
            }
        } elseif ($data['forgot_password']) {
            // default to post value
            if (true === empty($email)) {
                $email = $data['email'];
            }
    
            // check email is valid
            if (false === is_email($email)) {
                $this->show_error(['email']);
            }
    
            // check user exists
            $user = sql_query('SELECT id FROM ' . $this->table . "
                WHERE
                    email = '" . escape($email) . "'
            ", 1);
    
            if ($user) {
                if ($_POST['validate']) {
                    print 1;
                    exit;
                }
            } else {
                $this->show_error(['email is not in use']);
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
            $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $request . '?user=' . $user['id'] . '&code=' . $code;

            email_template($email, 'Password Reminder', $reps);
    
            $result = [
                'code' => 1,
                'message' => 'Password recovery email sent',
            ];
        }
        
        return $result;
    }

    public function login($data = null)
    {
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
            $result['message'] = 'User logged in';
        } elseif ($data['login']) {
            $errors = [];
            if ($data['email'] && $data['password']) {
                $this->check_login_attempts();
    
                $data['password'] = $this->create_hash($data['password']);
    
                $login_str = $this->get_login_str();
    
                $row = sql_query('SELECT * FROM ' . $this->table . "
                    WHERE
                        email='" . escape($data['email']) . "' AND
                        password='" . escape($data['password']) . "'
                        " . $login_str
                , 1);
    
                if ($row) {
                    $this->user = $row;
                    $_SESSION[$this->cookie_prefix . '_user'] = $this->user;
                    $_SESSION[$this->cookie_prefix . '_expires'] = time() + $this->expiry;
    
                    if ($this->log_last_login) {
                        sql_query('UPDATE ' . $this->table . " SET
                            last_login=NOW()
                            WHERE
                                id='" . escape($row['id']) . "'
                            LIMIT 1
                        ");
                    }
                    
                    $this->set_login($data['email'], $data['password']);

                    $result['code'] = 1;
                    $result['message'] = 'User logged in';
                } else {
                    $errors[] = 'password incorrect';
                    $this->failed_login_attempt($data['email'], $data['password']);
                }
            } elseif (!$data['email']) {
                $errors[] = 'email required';
            } elseif (!$data['password']) {
                $errors[] = 'password required';
            }
    
            if (count($errors)) {
                $this->show_error($errors);
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

    public function check_admin()
    {
        if (!$this->user['admin']) {
            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('?option=login');
        }
    }

    /**
     * @param bool $redirect
     */
    public function logout(bool $redirect = true)
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
        
        return true;
    }
}
