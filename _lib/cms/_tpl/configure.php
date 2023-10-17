<?php

/*
New config screen:
*/

if (1 != $auth->user['admin']) {
    die('access denied');
}

global $last_modified, $db_config, $auth_config, $shop_config, $shop_enabled, $from_email, $tpl_config, $vars, $count, $section, $table, $fields;

//print_r($tables);exit;

// internal tables
$default_tables = [
    'files' => [
        'date' => 'date',
        'name' => 'text',
        'size' => 'text',
        'type' => 'text',
        'id' => 'id',
    ],

    'cms privileges' => [
        'user' => 'text',
        'section' => 'text',
        'access' => 'integer',
        'filter' => 'text',
        'id' => 'id',
        'indexes' => [[
            'name' => 'user',
            'type' => 'index',
            'fields' => ['user'],
        ]],
    ],

    'cms filters' => [
        'user' => 'text',
        'section' => 'text',
        'name' => 'text',
        'filter' => 'textarea',
        'id' => 'id',
        'indexes' => [[
            'name' => 'user',
            'type' => 'index',
            'fields' => ['user'],
        ]],
    ],

    'cms multiple select' => [
        'section' => 'text',
        'field' => 'text',
        'item' => 'integer',
        'value' => 'text',
        'id' => 'id',
        'indexes' => [[
            'name' => 'section_field_item',
            'type' => 'index',
            'fields' => ['section', 'field', 'item'],
        ], [
            'name' => 'section_field_item_value',
            'type' => 'index',
            'fields' => ['section', 'field', 'item', 'value'],
        ]],
    ],

    'cms logs' => [
        'user' => 'select',
        'section' => 'text',
        'item' => 'integer',
        'task' => 'text',
        'details' => 'textarea',
        'date' => 'timestamp',
        'id' => 'id',
        'indexes' => [[
            'name' => 'section_item',
            'type' => 'index',
            'fields' => ['section', 'item'],
        ]],
    ],

    'cms activation' => [
        'user' => 'combo',
        'code' => 'text',
        'expiration' => 'timestamp',
        'id' => 'id',
        'indexes' => [[
            'name' => 'user',
            'type' => 'unique',
            'fields' => ['user'],
        ]],
    ],

    'cms login attempts' => [
        'email' => 'email',
        'ip' => 'ip',
        'date' => 'timestamp',
        'id' => 'id',
        'indexes' => [[
            'name' => 'ip_date',
            'type' => 'index',
            'fields' => ['ip', 'date'],
        ]],
    ],
];

//section templates
$section_templates = [
    'blank' => [],
    'pages' => [
        'heading' => 'text',
        'copy' => 'editor',
        'page name' => 'page-name',
        'page title' => 'text',
        'meta description' => 'textarea',
        'id' => 'id',
    ],
    'page' => [
        'heading' => 'text',
        'copy' => 'editor',
        'meta description' => 'textarea',
    ],
    'blog' => [
        'heading' => 'text',
        'copy' => 'editor',
        'tags' => 'textarea',
        'blog category' => 'checkboxes',
        'date' => 'timestamp',
        'page name' => 'page-name',
        'display' => 'checkbox',
        'id' => 'id',
    ],
    'blog categories' => [
        'category' => 'text',
        'page name' => 'page-name',
        'id' => 'id',
    ],
    'news' => [
        'heading' => 'text',
        'copy' => 'editor',
        'date' => 'timestamp',
        'meta description' => 'textarea',
        'page name' => 'page-name',
        'id' => 'id',
    ],
    'comments' => [
        'name' => 'text',
        'email' => 'email',
        'website' => 'url',
        'comment' => 'textarea',
        'blog' => 'select',
        'date' => 'timestamp',
        'ip' => 'ip',
        'id' => 'id',
    ],
    'enquiries' => [
        'name' => 'text',
        'email' => 'email',
        'tel' => 'tel',
        'enquiry' => 'textarea',
        'date' => 'timestamp',
        'read' => 'read',
        'id' => 'id',
    ],
    'email templates' => [
        'subject' => 'text',
        'body' => 'editor',
        'id' => 'id',
    ],
];

function array_to_csv($array): ?string // returns null or string
{
    if (null === $array) {
        return '';
    }

    foreach ($array as $k => $v) {
        $array[$k] = "\t'" . addslashes(trim($v)) . "'";
    }

    return implode(",\n", $array);
}

function str_to_csv($str): string
{
    if (!$str) {
        return '';
    }

    $array = explode("\n", $str);

    foreach ($array as $k => $v) {
        $array[$k] = "\t'" . addslashes(trim($v)) . "'" . '';
    }

    return implode(",\n", $array);
}

function str_to_assoc($str): array
{
    if (!$str) {
        return [];
    }

    $lines = explode("\n", $str);

    $array = [];
    foreach ($lines as $k => $v) {
        $pos = strpos($v, '=');
        $array[trim(substr($v, 0, $pos))] = trim(substr($v, $pos + 1));
    }

    return $array;
}

function str_to_assoc_str($str): string
{
    if (!$str) {
        return '';
    }

    $array = explode("\n", $str);

    foreach ($array as $k => $v) {
        $pos = strpos($v, '=');
        $array[$k] = "\t'" . addslashes(trim(substr($v, 0, $pos))) . "'=>'" . addslashes(trim(substr($v, $pos + 1))) . "'" . '';
    }

    return implode(",\n", $array);
}

function str_to_bool($str): string
{
    if ($str) {
        return 'true';
    }
    return 'false';
}

function get_db_field($table, $field_name) {
    $old_field = [];
    
    $cols = sql_query("SHOW FULL COLUMNS FROM `" . escape($table) . "`");

    foreach ($cols as $field) {
        if ($field['Field'] == underscored($field_name)) {
            $db_field = $field['Type'];
            $null = $field['Null'] === 'NO' ? 'NOT NULL' : '';
            $old_field = $field;
            break;
        }
    }
    
    return $old_field;
}

// generate field list from the components dir
$field_opts = [];
foreach (glob(__DIR__ . '/../components/*.php') as $filename) {
    $filename = str_replace('.php', '', basename($filename));
    $field_opts[] = camelCaseToSnakeCase($filename);
}

//check config file
global $root_folder;
$config_file = $root_folder . '/_inc/config.php';

if ($_POST['cmd']) {

    try {
        switch ($_POST['cmd']) {
            case 'add_table':
                if (!$_POST['name']) {
                    throw new Exception('Missing name');
                }

                $query = 'CREATE TABLE `' . escape(underscored($_POST['name'])) . '` ( `id` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`)) ENGINE = InnoDB';
                sql_query($query);
                break;
            case 'rename_table':
                if (!$_POST['table']) {
                    throw new Exception('Missing table');
                }
                if (!$_POST['name']) {
                    throw new Exception('Missing name');
                }

                $query = 'RENAME TABLE `' . escape($_POST['table']) . '`  TO `' . escape(underscored($_POST['name'])) . '`';
                sql_query($query);
                break;
            case 'delete_table':
                if (in_array($v, ['id'])) {
                    //don't drop id!
                    break;
                }

                if (!$_POST['table']) {
                    throw new Exception('Missing table');
                }

                $query = 'DROP TABLE IF EXISTS `' . escape($_POST['table']) . '`';
                sql_query($query);
                break;
            case 'add_field':
                case 'edit_field':
                    if (!$_POST['name']) {
                        throw new Exception('Missing name');
                    }
                    if (!$_POST['type']) {
                        throw new Exception('Missing field');
                    }
                    if (!$_POST['table']) {
                        throw new Exception('Missing field');
                    }
                    
                    $old_field = get_db_field($_POST['table'], $_POST['field']);

                    $db_field = $old_field['Type'] ?: $this->form_to_db($_POST['type']);
                    $comment = $_POST['type'] . '|' . ($_POST['required'] ? 1 : 0) . '|' . $_POST['label'];
                    $null = $old_field['Null'] === 'NO' ? 'NOT NULL' : '';
                    $action = $_POST['cmd'] == 'add_field' ? 'ADD' : 'CHANGE `' . underscored($_POST['field']) . '`';
                    $collation = $old_field['Collation'] ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci' :  '';
                    $extra = ($_POST['name'] === 'id') ? 'AUTO_INCREMENT' : '';

                    $query = "ALTER TABLE `" . escape($_POST['table']) . "` " . $action . ' `' . underscored($_POST['name']) . '` ' . $db_field . ' ' . $collation . ' ' . $null . " " . $extra . " COMMENT '" . $comment . "' ";

                    sql_query($query);
                    break;
                case 'move_field':
                    if (!$_POST['table']) {
                        throw new Exception('Missing table');
                    }

                    if (!$_POST['field']) {
                        throw new Exception('Missing field');
                    }

                    $old_field = get_db_field($_POST['table'], $_POST['field']);
                    
                    $db_field = $old_field['Type'];
                    $comment = $old_field['Comment'];
                    $null = $old_field['Null'] === 'NO' ? 'NOT NULL' : '';
                    $action = 'MODIFY';
                    $collation = $old_field['Collation'] ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci' :  '';
                    $default = $old_field['Default'] ? 'DEFAULT ' . $old_field['Default'] : '';

                    $query = "ALTER TABLE `" . escape($_POST['table']) . '` ' . $action . ' `' . underscored($_POST['field']) . '` ' . $db_field . ' ' . $collation . ' ' . $null . " " . $default . " COMMENT '" . $comment . "' " . ($_POST['after'] ? ' AFTER `' . $_POST['after'] . '`' : 'FIRST');

                    sql_query($query);
                    break;
                case 'delete_field':
                    if (in_array($v, ['id'])) {
                        //don't drop id!
                        break;
                    }

                    if (!$_POST['table']) {
                        throw new Exception('Missing table');
                    }

                    if (!$_POST['column']) {
                        throw new Exception('Missing column');
                    }

                    $query = "ALTER TABLE `" . escape($_POST['table']) . "` DROP `" . escape($_POST['column']) . '`';
                    sql_query($query);
                    break;
                case 'get':
                    
                    // get tables
                    $tables = [];
                    $rows = sql_query("SHOW TABLES FROM `" . escape($db_config['name']) . "`");
                    foreach ($rows as $row) {
                        $table = current($row);
                        $section = spaced($table);

                        $fields = sql_query("SHOW FULL COLUMNS FROM `" . escape($table) . "`");

                        //print_r($fields);

                        foreach ($fields as $field) {
                            $field_name = $field['Field'];
                            $name = spaced($field_name);

                            if ($field['Comment']) {
                                $parts = explode('|', $field['Comment']);
                                $type = $parts[0];
                                $required = $parts[1];
                                $label = $parts[2];
                            } else {
                                $type = $vars["fields"][$section][$name];
                                $required = is_array($vars["required"][$section]) && in_array($name, $vars["required"][$section]);
                                $label = $vars["label"][$section][$name];
                            }

                            $tables[$table][] = [
                                'name' => $field_name,
                                'type' => $type,
                                'required' => $required,
                                'label' => $label,
                            ];
                        }
                    }

                    $response['tables'] = $tables;
                    break;
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    $response['success'] = $response['error'] ? false : true;

    print json_encode($response);
    exit;
}

if ($_POST['save']) {
    if (false === file_exists($config_file)) {
        die('Error: config file does not exist: ' . $config_file);
    }
    
    if (!is_writable($config_file)) {
        die('Error: config file is not writable: ' . $config_file);
    }
    
    if (!$_POST['last']) {
        die('Error: form submission incomplete');
    }

    if ($_POST['last_modified'] != $last_modified) {
        die('Error: changes since last modified');
    }
    
    // DATABASE UPGRADE
    if ($vars['fields']) {
        // go through fields and update field comments
        foreach($vars['fields'] as $section => $fields) {
            
            if (!count($fields)) {
                continue;
            }
            
            $table = underscored($section);
            $has_id = false;
            
            foreach ($fields as $name => $type) {
                if ($type == 'id') {
                    $has_id = true;
                }
        
                // convert checkboxes to select multiple
                $convert = false;
                $db_field = '';
                $null = '';
                $collation = '';
                $old_field = [];
                
                if ($type === 'checkboxes') {
                    $type = 'select_multiple';
                    $convert = true;
                    $db_field = $this->form_to_db($type);
                    $action = 'ADD';
                    
                    // remove old checkboxes field if it exists
                    sql_query("ALTER TABLE `" . escape($table) . "` DROP IF EXISTS `" . underscored($name) . "`");
                } else {
                    $old_field = get_db_field($table, $name);
                    $db_field = $old_field['Type'];
                    $null = $old_field['Null'] === 'NO' ? 'NOT NULL' : '';
                    
                	// skip if field does not exist
	                if (!$db_field) {
	                	continue;
	                }
                    
                    $action = 'MODIFY';
                }
                
                $comment = $type . '|' . (in_array($name, $vars["required"][$section]) ? 1 : 0) . '|' . $vars['label'][$section][$name];
                
                if ($comment === $old_field['Comment']) {
                    continue;
                }
                
                $extra = ($name === 'id') ? 'AUTO_INCREMENT' : '';
                
                if ($old_field['Extra'] === 'VIRTUAL GENERATED') {
                    $action = 'CHANGE';
                    
                    // get generated column definition
                    $schema = sql_query("SELECT table_schema, column_name, generation_expression
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE 
                            TABLE_NAME = '" . escape($table) . "' AND
                            COLUMN_NAME = '" . underscored($name) . "' AND
                            TABLE_SCHEMA = '" . $db_config['name'] . "'
                    ", 1);
                    
                    //var_dump($schema); exit;
                    
                    // ALTER TABLE `orders` CHANGE `delivery_duration` `delivery_duration` INT(11) AS (timestampdiff(MINUTE,`wanted_date`,`complete_date`)) VIRTUAL COMMENT 'int|0|';
                    
                    $extra = 'AS (' . $schema['generation_expression'] . ') VIRTUAL';
                    
                    $query = "ALTER TABLE `" . escape($table) . '` ' . $action . ' `' . underscored($name) . '` `' . underscored($name) . '` ' . $db_field . " " . $null . " " . $extra . " COMMENT '" . $comment . "'";
                    
                } else {
                    
                    $collation = $old_field['Collation'] ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci' :  '';
                    
                    $query = "ALTER TABLE `" . escape($table) . '` ' . $action . ' `' . underscored($name) . '` ' . $db_field . ' ' . $collation . ' ' . $null . " " . $extra . " COMMENT '" . $comment . "'";
                }
                
                //print_r($old_field);
                //print($query);
                
                sql_query($query);
                
                if ($convert) {
                    $rows = sql_query("SELECT id FROM `" . escape($table) . '`');
                    foreach($rows as $row) {
                        $vals = sql_query("SELECT value FROM cms_multiple_select WHERE
                            section = '" . $section . "' AND
                            field = '" . $name . "' AND
                            item = '" . $row['id'] . "'
                        ");
                        
                        $data = [];
                        foreach($vals as $val) {
                            $data[] = $val['value'];
                        }
                        
                        sql_query("UPDATE `" . escape($table) . "` SET
                            `" . underscored($name) . "` = '".escape(json_encode($data))."'
                            WHERE
                                id = '". $row['id'] ."'
                        ");
                    }
                }
            }
            
            if (!$has_id) {
                $col = sql_query("SHOW COLUMNS FROM `" . $table . "` LIKE 'id'", 1);
                
                if ($col) {
                    sql_query("ALTER TABLE `" . $table . "` DROP COLUMN `id`");
                }
            }
        }
    }

    // check internal tables
    foreach ($default_tables as $name => $fields) {
        $this->check_table(underscored($name), $fields);
    }

    $count['sections'] = 0;
    $count['subsections'] = 0;
    $count['options'] = 0;

    $display = [];
    foreach ($_POST['sections'] as $section_id => $section) {
        if (is_array($section)) {
            continue;
        }
        
        $display[] = $section;
    }

    //hash passwords
    if (!$auth_config['hash_password'] and $_POST['auth_config']['hash_password']) {
        $users = sql_query('SELECT * FROM users');

        $auth->hash_password = true;
        foreach ($users as $user) {
            $password = $auth->create_hash($user['password']);
            sql_query("UPDATE users SET
                password = '" . escape($password) . "'
                WHERE
                    id = '" . $user['id'] . "'
            ");
        }
    }

    $config = '<?php
$last_modified = ' . time() . ';
# GENERAL SETTINGS
$db_config["host"] = ' . ($_SERVER["DB_HOST"] ? '$_SERVER["DB_HOST"]' : '"' . $db_config['host'] . '"') . ';
$db_config["user"] = ' . ($_SERVER["DB_USER"] ? '$_SERVER["DB_USER"]' : '"' . $db_config['user'] . '"') . ';
$db_config["pass"] = ' . ($_SERVER["DB_PASS"] ? '$_SERVER["DB_PASS"]' : '"' . $db_config['pass'] . '"') . ';
$db_config["name"] = ' . ($_SERVER["DB_NAME"] ? '$_SERVER["DB_NAME"]' : '"' . $db_config['name'] . '"') . ';

#TPL

// multipage templates
$tpl_config["catchers"] = [' . str_to_csv($_POST['tpl_config']['catchers']) . '];

// 301 redirects
$tpl_config["redirects"] = [' . str_to_assoc_str($_POST['tpl_config']['redirects']) . '];

# USER LOGIN
$auth_config = [];

// table where your users are stored
$auth_config["table"] = "' . $_POST['auth_config']['table'] . '";

// required fields when registering and updating
$auth_config["required"] = [
    ' . array_to_csv($_POST['auth_config']['required']) . '
];

// automated emails will be sent from this address
$from_email = "' . $_POST['from_email'] . '";
$auth_config["from_email"] = $from_email;

// hash passwords
$auth_config["hash_password"] = ' . str_to_bool($_POST['auth_config']['hash_password']) . ';
$auth_config["hash_salt"] = "' . $_POST['auth_config']['hash_salt'] . '";

// email activation
$auth_config["email_activation"] = ' . str_to_bool($_POST['auth_config']['email_activation']) . ';

// use a secret term to encrypt cookies
$auth_config["secret_phrase"] = "' . $_POST['auth_config']['secret_phrase'] . '";

// for use with session and cookie vars
$auth_config["cookie_prefix"] = "' . $_POST['auth_config']['cookie_prefix'] . '";

// how long a cookie lasts with remember me
$auth_config["cookie_duration"] = "' . $_POST['auth_config']['cookie_duration'] . '";

// Single Sign-on credentials
$auth_config["facebook_appId"] = "' . $_POST['auth_config']['facebook_appId'] . '" ?: $_SERVER["facebook_appId"];
$auth_config["facebook_secret"] = "' . $_POST['auth_config']['facebook_secret'] . '" ?: $_SERVER["facebook_secret"];

$auth_config["google_appId"] = "' . $_POST['auth_config']['google_appId'] . '" ?: $_SERVER["google_appId"];
$auth_config["google_secret"] = "' . $_POST['auth_config']['google_secret'] . '" ?: $_SERVER["google_secret"];

$auth_config["recaptcha_key"] = "' . $_POST['auth_config']['recaptcha_key'] . '" ?: $_SERVER["recaptcha_key"];
$auth_config["recaptcha_secret"] = "' . $_POST['auth_config']['recaptcha_secret'] . '" ?: $_SERVER["recaptcha_secret"];
$auth_config["recaptcha_threshold"] = "' . $_POST['auth_config']['recaptcha_threshold'] . '" ?: "0.5";

# ADMIN AREA
// sections in admin navigation
$vars["sections"] = [
    ' . array_to_csv($display) . '
];

';

    // fields in each section
    foreach ($_POST['sections'] as $section => $v) {
        if (!is_string($section)) {
            continue;
        }
        
        $subsections = '';
        
        foreach ($_POST['sections'][$section] as $subsection) {
            $subsections .= '"' . $subsection . '",';
        }

        $config .= '
$vars["subsections"]["' . $section . '"] = [' . $subsections . '];
';
    }

    $config .= '
# SHOP
$shop_enabled = ' . str_to_bool($_POST['shop_enabled']) . ';
$shop_config["paypal_email"] = "' . $_POST['shop_config']['paypal_email'] . '";
$shop_config["include_vat"] = ' . str_to_bool($_POST['shop_config']['include_vat']) . ';

# OPTIONS
    ';

    foreach ($_POST['options'] as $option) {
        while (in_array($option['name'], (array)$field_options)) {
            $index = array_search($option['name'], $field_options);
            unset($field_options[$index]);
        }

        if ($option['section']) {
            $config .= '
$vars["options"]["' . spaced($option['name']) . '"] = "' . $option['section'] . '";
            ';
        } else {
            $option['list'] = strip_tags($option['list']);

            if (strstr($option['list'], '=')) {
                $config .= '
$vars["options"]["' . spaced($option['name']) . '"] = [
                ' . str_to_assoc_str($option['list']) . '
];
                ';
            } else {
                $config .= '
$vars["options"]["' . spaced($option['name']) . '"] = [
                ' . str_to_csv($option['list']) . '
];
                ';
            }
        }
    }

    foreach ($field_options as $field_option) {
        if (!in_array($field_option, $_POST['options'])) {
            $config .= '
$vars["options"]["' . $field_option . '"] = "";
            ';
        }
    }

    //die($config);
    file_put_contents($config_file, $config);
    
    // clear config.php cache
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    unset($_POST);

    $_SESSION['message'] = 'Configuration Saved';
    
    reload();
} else {

    // version check
    $release_url = 'https://api.github.com/repos/adamjimenez/shiftlib/releases/latest';
    $release = wget($release_url);

    // zipball_url
    /*
if ($release['tag_name'] != $this::VERSION) {
    ?>
<div class="alert alert-primary mt-3" role="alert">
    New version available: <?=$release['tag_name']; ?>
    <p>
        <?=$release['body']; ?>
    </p>
    <a href="?option=upgrade">Upgrade now</a>
</div>
<?php
}
*/

    $count['sections'] = 0;
    $count['subsections'] = 0;
    $count['options'] = 0;

$vars2 = $vars;

// preserve key order
foreach($vars2['options'] as $k => $v) {
    if (is_assoc_array($v)) {
        $arr = [];
        foreach ($v as $k2 => $v2) {
            $arr[$k2 . '#'] = $v2;
        }
        
        $vars2['options'][$k] = $arr;
    }
}

    ?>

    <style>
        .toggle_section {
            cursor: pointer;
        }
    </style>

    <div id="app">

    <div class="main-content-inner">
        <div class="item">
            <div class="col-lg-12 mt-1 p-0">
                <div class="card">
                    <div class="card-body">

                        <form method="post" id="form">

                            <?php if ($vars['fields']) { ?>
                            <div class="alert alert-warning" role="alert">
                              Database upgrade required.
                              Backup your database and config file then save to proceed.
                            </div>
                            <?php } ?>
                            
                            <input type="hidden" name="save" value="1">
                            <input type="hidden" name="last_modified" value="<?=$last_modified; ?>">

                            <ul class="nav nav-tabs mt-3" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="pills-summary-tab" data-toggle="pill" href="#tables" role="tab" aria-controls="pills-tables" aria-selected="true">
                                        Tables
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-summary-tab" data-toggle="pill" href="#sections" role="tab" aria-controls="pills-sections" aria-selected="true">
                                        Sections
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-dropdowns-tab" data-toggle="pill" href="#dropdowns" role="tab" aria-controls="pills-dropdowns" aria-selected="true">
                                        Dropdowns
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-general-tab" data-toggle="pill" href="#general" role="tab" aria-controls="pills-general" aria-selected="true">
                                        General
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-template-tab" data-toggle="pill" href="#template" role="tab" aria-controls="pills-template" aria-selected="true">
                                        Template
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-login-tab" data-toggle="pill" href="#login" role="tab" aria-controls="pills-login" aria-selected="true">
                                        Login
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content py-3">

                                <div class="tab-pane fade show active" id="tables" role="tabpanel" aria-labelledby="pills-tables-tab">

                                    <div class="items m-0">
                                        
                                        <div class="table item mb-3 hbox px-3" v-for="(fields, table) in tables" :index="table">
                                            <div>
                                                <span class="toggle_section px-1"><i class="fas fa-chevron-right"></i></span>
                                            </div>
                                            <div>
                                                <div>
                                                    <div class="table_name" style="display: inline-block; width: 200px;" @click="renameTable(table)">
                                                        {{ table }}
                                                    </div>
                                                    <span class="delTable" @click="deleteTable(table)"><i class="fas fa-trash"></i></span>
                                                </div>
                                
                                                <div class="settings" style="display:none;">
                                                    <div class="fields">
                                                        <div class="items">
                                                            
                                                            <div class="field item my-3 draggable hbox" v-for="field in fields">
                                                                <div class="handle mx-2">
                                                                    <i class="fas fa-square"></i>
                                                                </div>
                                                                <div class="flex">
                                                                    <label class="field_name" @click="editField(field, table)">{{field.name}}</label>
                                                                </div>
                                                                <div>
                                                                    <label class="label">{{field.label}}</label>
                                                                </div>
                                                                <div>
                                                                    <label class="type px-3">{{field.type}}</label>
                                                                </div>
                                                                <div>
                                                                    <label class="required">{{field.required > 0 ? 'required' : ''}}</label>
                                                                </div>
                                                                <div>
                                                                    <span class="delField ml-2" @click="delField(field.name, table)"><i class="fas fa-trash"></i></span>
                                                                </div>
                                                            </div>
                                                            
                                                        </div>
                                                        <span class="addField" @click="addField(table)"><i class="fas fa-plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>

                                    <p class="mt-3">
                                        <button class="btn btn-secondary addTable" type="button" @click="addTable"><i class="fas fa-plus"></i></button>
                                    </p>

                                </div>

                                <div class="tab-pane fade show" id="sections" role="tabpanel" aria-labelledby="pills-sections-tab">

                                    <div class="items m-0">

                                        <div class="section item mb-3 draggableSections hbox" v-for="section in sections">
                                            <div class="p-2">
                                                <div class="handle" style="height:100%;">
                                                    <i class="fas fa-square"></i>
                                                </div>
                                                <span class="toggle_section px-1"><i class="fas fa-chevron-right"></i></span>
                                            </div>
                                            <div class="flex">
                                                <p>
                                                    <input class="name" type="text" name="sections[]" :value="section" placeholder="Section name">
                                                    <span class="p-1" @click="deleteSection(section)"><i class="fas fa-trash"></i></span>
                                                </p>
                                
                                                <div class="settings" style="display:none;">
                                                    <div class="subsections">
                                                        <div class="items">
                                                            <div class="hbox draggable p-2 item" v-for="subsection in subsections[section]"> 
                                                                <div class="handle p-2">
                                                                    <i class="fas fa-square"></i>
                                                                </div>
                                                                <div class="p-2">
                                                                    <input class="subsection" :name="'sections[' + section + '][]'" :value="subsection">
                                                                </div>
                                                                <div class="p-2">
                                                                    <span @click="deleteSubsection(subsection, section)"><i class="fas fa-trash"></i></span>
                                                                </div>
                                                            </div>                                                            
                                                        </div>
                                                        <span class="btn" @click="addSubsection(section)"><i class="fas fa-plus"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>

                                    <p class="mt-3">
                                        <button class="btn btn-secondary addSection" type="button" @click="addSection"><i class="fas fa-plus"></i></button>
                                    </p>

                                </div>

                                <div class="tab-pane fade" id="dropdowns" role="tabpanel" aria-labelledby="pills-dropdowns-tab">

                                    <div class="items m-0" v-for="option in options" :index="option">
                                        
                                        <div class="item mb-3 list hbox" style="max-width: 600px;">
                                            <div>
                                                <input class="name" type="text" :name="'options[' + option.name + '][name]'" v-model="option.name">
                                            </div>
                                            <div class="px-3 flex">
                                                <textarea v-if="option.list" cols="30" type="text" :name="'options[' + option.name + '][list]'" class="autosize" v-model="option.value"></textarea>
                                                
                                                <select v-else class="section" :name="'options[' + option.name + '][section]'" v-model="option.value">
                                                    <template v-for="(fields, table) in tables" :index="table">
                                                        <option :value="table">{{table}}</option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <button class="btn" type="button" @click="toggleOption(option)"> <i class="fas fa-list"></i></button>
                                                <button class="btn" type="button" @click="deleteOption(option)"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>                                        
                                        
                                    </div>
                                    <span class="btn btn-secondary" @click="addOption"><i class="fas fa-plus"></i></span>

                                </div>

                                <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="pills-general-tab">

                                    <label>From email</label><br>
                                    <input type="email" name="from_email" value="<?=$from_email; ?>">
                                    <br>
                                    <br>

                                    <label>Shopping cart</label><br>
                                    <input type="checkbox" name="shop_enabled" value="1" <?php if ($shop_enabled) { ?> checked<?php } ?>>
                                    <br>
                                    <br>

                                    <label>Paypal email</label><br>
                                    <input type="email" name="shop_config[paypal_email]" value="<?=$shop_config['paypal_email']; ?>">
                                    <br>
                                    <br>

                                    <label>VAT</label><br>
                                    <input type="checkbox" name="shop_config[include_vat]" value="1" <?php if ($shop_config['include_vat']) { ?> checked<?php } ?>>
                                    <br>
                                    <br>

                                </div>

                                <div class="tab-pane fade" id="template" role="tabpanel" aria-labelledby="pills-template-tab">

                                    <div style="padding:5px 10px;">
                                        <label>Catchers</label><br>
                                        <textarea name="tpl_config[catchers]" class="autosize" style="width: 100%;" placeholder="e.g. pages, one per line"><?=implode("\n", $tpl_config['catchers']); ?></textarea>
                                        <br>
                                        <br>

                                        <label>Redirects</label><br>
                                        <?php
                                        $redirects = '';
                                        foreach ($tpl_config['redirects'] as $k => $v) {
                                            $redirects .= $k . '=' . $v . "\n";
                                        }
                                        $redirects = trim($redirects);
                                        ?>
                                        <textarea name="tpl_config[redirects]" class="autosize" style="width: 100%;" placeholder="e.g. oldpage=newpage, one per line"><?=$redirects; ?></textarea>
                                        <br>
                                        <br>
                                    </div>

                                </div>

                                <div class="tab-pane fade" id="login" role="tabpanel" aria-labelledby="pills-login-tab">

                                    <label>Table</label><br>
                                    <input type="text" name="auth_config[table]" value="<?=$auth_config['table']; ?>">
                                    <br>
                                    <br>

                                    <label>Hash passwords</label><br>
                                    <input type="checkbox" name="auth_config[hash_password]" value="1" <?php if ($auth_config['hash_password']) { ?> checked<?php } ?>>
                                    <br>
                                    <br>

                                    <label>Hash salt</label><br>
                                    <input type="text" name="auth_config[hash_salt]" value="<?=$auth_config['hash_salt']; ?>">
                                    <br>
                                    <br>

                                    <label>Require email activation</label><br>
                                    <input type="checkbox" name="auth_config[email_activation]" value="1" <?php if ($auth_config['email_activation']) { ?> checked<?php } ?>>
                                    <br>
                                    <br>

                                    <label>Cookie salt</label><br>
                                    <input type="text" name="auth_config[secret_phrase]" value="<?=$auth_config['secret_phrase']; ?>">
                                    <br>
                                    <br>

                                    <label>Cookie prefix</label><br>
                                    <input type="text" name="auth_config[cookie_prefix]" value="<?=$auth_config['cookie_prefix']; ?>">
                                    <br>
                                    <br>

                                    <label>Cookie duration</label><br>
                                    <input type="text" name="auth_config[cookie_duration]" value="<?=$auth_config['cookie_duration']; ?>">
                                    <br>
                                    <br>

                                    <label>Facebook appId</label><br>
                                    <input type="text" name="auth_config[facebook_appId]" value="<?=$auth_config['facebook_appId']; ?>">
                                    <br>
                                    <br>

                                    <label>Facebook secret</label><br>
                                    <input type="text" name="auth_config[facebook_secret]" value="<?=$auth_config['facebook_secret']; ?>">
                                    <br>
                                    <br>

                                    <label>Google appId</label><br>
                                    <input type="text" name="auth_config[google_appId]" value="<?=$auth_config['google_appId']; ?>">
                                    <br>
                                    <br>

                                    <label>Google secret</label><br>
                                    <input type="text" name="auth_config[google_secret]" value="<?=$auth_config['google_secret']; ?>">
                                    <br>
                                    <br>

                                    <label>ReCAPTHA key</label><br>
                                    <input type="text" name="auth_config[recaptcha_key]" value="<?=$auth_config['recaptcha_key']; ?>">
                                    <br>
                                    <br>

                                    <label>ReCAPTHA secret</label><br>
                                    <input type="text" name="auth_config[recaptcha_secret]" value="<?=$auth_config['recaptcha_secret']; ?>">
                                    <br>
                                    <br>

                                    <label>Score Threshold</label><br>
                                    <input type="text" name="auth_config[recaptcha_threshold]" value="<?=$auth_config['recaptcha_threshold']; ?>">
                                    <br>
                                    <br>

                                </div>

                            </div>

                            <p>
                                <button class="btn btn-secondary" type="submit" onclick="return confirm('WARNING: changing settings can result in loss of data or functionality. Are you sure you want to continue?');">Save config</button>
                            </p>

                            <input type="hidden" name="last" value="1">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal renameTable" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Table name</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form>
                    <input type="hidden" name="cmd" value="rename_table">
                    <input type="hidden" name="table" value="">
                    <div class="modal-body">
                        <input type="text" name="name" class="name w-100" required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary save" @click="saveTable()">Save</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal editField" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit field</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form>
                    <input type="hidden" name="cmd" value="edit_field">
                    <input type="hidden" name="table" value="">
                    <input type="hidden" name="field" value="">
                    <div class="modal-body">
                        <div class="form-group">
                            <input type="text" name="name" class="name" required placeholder="Name">
                        </div>
                        <div class="form-group">
                            <select class="type" name="type" required>
                                <?=html_options($field_opts); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="label" class="label" required placeholder="Label">
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="required" value="1" class="required" required> required</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary save" @click="saveField()">Save</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal addSectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add section</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <select name="section">
                                <template v-for="(fields, table) in tables" :index="table">
                                    <option :value="table">{{table}}</option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary save" @click="saveSection()">Save</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal addSubsectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add subsection</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form>
                    <input type="hidden" name="section" value="">
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <select name="subsection">
                                <template v-for="(fields, table) in tables" :index="table">
                                    <option :value="table">{{table}}</option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary save" @click="saveSubsection()">Save</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    
    </div>

    <script>
        var section_templates = <?=json_encode($section_templates); ?>;
        var count = <?=json_encode($count); ?>;
        var vars = <?=json_encode($vars2); ?>;
        var max_input_vars = '<?=ini_get('max_input_vars'); ?>';
    </script>

    <script src="/_lib/cms/assets/js/configure.js?t=<?=time();?>"></script>

    <?php
}