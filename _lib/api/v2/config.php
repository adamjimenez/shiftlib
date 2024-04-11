<?php
require('../../base.php');
require(__DIR__ . "/../includes/cors.php");

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

global $last_modified,
$db_config,
$auth_config,
$shop_config,
$shop_enabled,
$from_email,
$tpl_config,
$vars,
$section,
$table,
$fields;

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
			'fields' => ['section',
				'item'],
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

	'cms reports' => [
		'user' => 'text',
		'title' => 'text',
		'report' => 'textarea',
		'created' => 'timestamp',
		'position' => 'position',
		'id' => 'id',
	],

	'cms login attempts' => [
		'email' => 'email',
		'ip' => 'ip',
		'date' => 'timestamp',
		'id' => 'id',
		'indexes' => [[
			'name' => 'ip_date',
			'type' => 'index',
			'fields' => ['ip',
				'date'],
		]],
	],

	'cms trusted devices' => [
		'user' => 'combo',
		'useragent' => 'text',
		'hash' => 'text',
		'ip' => 'ip',
		'id' => 'id',
		'indexes' => [[
			'name' => 'hash_user',
			'type' => 'unique',
			'fields' => ['hash', 'user'],
		]],
	],
	
	'email templates' => [
		'subject' => 'text',
		'body' => 'editor',
		'id' => 'id',
	],
];

// check config file
global $root_folder;
$config_file = $root_folder . '/_inc/config.php';
$default_collation = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';

$response = [];

try {

	if (1 !== (int)$auth->user['admin']) {
		throw new Exception('access denied');
	}

	switch ($_GET['cmd']) {
		case 'save_table':
			if (!$_POST['name']) {
				throw new Exception('Missing name');
			}

			if ($_POST['id']) {
				$query = 'RENAME TABLE `' . escape($_POST['id']) . '`  TO `' . escape(underscored($_POST['name'])) . '`';
			} else {
				$query = 'CREATE TABLE `' . escape(underscored($_POST['name'])) . "` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT 'id|0|', PRIMARY KEY (`id`)) ENGINE = InnoDB";
			}
			sql_query($query);
			break;
		case 'delete_table':
			if (!$_POST['table']) {
				throw new Exception('Missing table');
			}

			$query = 'DROP TABLE IF EXISTS `' . escape($_POST['table']) . '`';
			sql_query($query);
			break;
		case 'save_field':
		case 'move_field':
			if (!$_POST['table']) {
				throw new Exception('Missing table');
			}
			
			$column = $_POST['name'] ?: $_POST['field'];
			
			if (!$column) {
				throw new Exception('Missing column');
			}
				
			if ($_GET['cmd'] === 'save_field') {
				if (!$_POST['type']) {
					throw new Exception('Missing type');
				}
	
				$old_field = $_POST['id'] ? get_db_field($_POST['table'], $_POST['id']) : [];
				
				$comment = $_POST['type'] . '|' . ($_POST['required'] ? 1 : 0) . '|' . escape($_POST['label']);
				$action = $_POST['id'] ? 'CHANGE `' . underscored($_POST['id']) . '`' : 'ADD';
				$after = '';
				
				if (!$_POST['id'] && $_POST['type'] === 'timestamp') {
					$default = 'CURRENT_TIMESTAMP';
				}
			} else {
				$old_field = get_db_field($_POST['table'], $column);
				
				$comment = $old_field['Comment'];
				$action = 'MODIFY';
				$after = $_POST['after'] ? ' AFTER `' . escape($_POST['after']) . '`' : 'FIRST';
			}

			$db_field = $old_field['Type'] ?: $cms->form_to_db($_POST['type']);
			$null = $old_field['Null'] === 'NO' ? 'NOT NULL' : '';
			$extra = ($old_field['Extra'] === 'auto_increment' || $column === 'id') ? 'AUTO_INCREMENT' : '';
			
			$default = $default ?: $old_field['Default'];
			$default = $default ? 'DEFAULT ' . $default : '';
			
			$collation = stristr($db_field, 'CHAR') || stristr($db_field, 'TEXT') ? $default_collation : '';
			
			$query = "ALTER TABLE `" . escape($_POST['table']) . "` " . $action . ' `' . underscored($column) . '` ' . $db_field . ' ' . $collation . ' ' . $null . ' ' . $extra . ' ' . $default . " COMMENT '" . $comment . "' " . $after;

			sql_query($query);

			break;
		case 'delete_field':
			if (in_array($_POST['column'], ['id'])) {
				//don't drop id!
				continue;
			}

			if (!$_POST['table']) {
				throw new Exception('Missing table');
			}

			if (!$_POST['field']) {
				throw new Exception('Missing field');
			}

			$query = "ALTER TABLE `" . escape($_POST['table']) . "` DROP `" . escape($_POST['field']) . '`';
			sql_query($query);
			break;

		case 'save':
			if (false === file_exists($config_file)) {
				throw new Exception('Error: config file does not exist: ' . $config_file);
			}

			if (!is_writable($config_file)) {
				throw new Exception('Error: config file is not writable: ' . $config_file);
			}

			if ($_POST['last_modified'] != $last_modified) {
				throw new Exception('Error: changes since last modified');
			}

			// DATABASE UPGRADE
			if ($vars['fields']) {
				// go through fields and update field comments
				foreach ($vars['fields'] as $section => $fields) {

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
							$db_field = $cms->form_to_db($type);
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
		                            TABLE_NAME = '" . $table . "' AND
		                            COLUMN_NAME = '" . underscored($name) . "' AND
		                            TABLE_SCHEMA = '" . $db_config['name'] . "'
		                    ", 1);

							$extra = 'AS (' . $schema['generation_expression'] . ') VIRTUAL';
						}
				
						$query = "ALTER TABLE `" . escape($table) . '` ' . $action . ' `' . underscored($name) . '` ' . $db_field . ' ' . $collation . ' ' . $null . ' ' . $extra . " COMMENT '" . $comment . "'";

						sql_query($query);

						if ($convert) {
							$rows = sql_query("SELECT id FROM `" . escape($table) . '`');
							foreach ($rows as $row) {
								$vals = sql_query("SELECT value FROM cms_multiple_select WHERE
		                            section = '" . escape($section) . "' AND
		                            field = '" . escape($name) . "' AND
		                            item = '" . (int)$row['id'] . "'
		                        ");

								$data = [];
								foreach ($vals as $val) {
									$data[] = $val['value'];
								}

								sql_query("UPDATE `" . escape($table) . "` SET
		                            `" . underscored($name) . "` = '".escape(json_encode($data))."'
		                            WHERE
		                                id = '". (int)$row['id'] ."'
		                        ");
							}
						}
					}

					if (!$has_id) {
						sql_query("ALTER TABLE `" . escape($table) . "` DROP IF EXISTS `id`");
					}
				}
			}

			// check internal tables
			foreach ($default_tables as $name => $fields) {
				$cms->check_table(underscored($name), $fields);
			}

			// hash passwords
			/*
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
			*/

			$config = '<?php
$last_modified = ' . time() . ';

# GENERAL SETTINGS
$db_config["host"] = ' . ($_SERVER["DB_HOST"] ? '$_SERVER["DB_HOST"]' : '"' . $db_config['host'] . '"') . ';
$db_config["user"] = ' . ($_SERVER["DB_USER"] ? '$_SERVER["DB_USER"]' : '"' . $db_config['user'] . '"') . ';
$db_config["pass"] = ' . ($_SERVER["DB_PASS"] ? '$_SERVER["DB_PASS"]' : '"' . $db_config['pass'] . '"') . ';
$db_config["name"] = ' . ($_SERVER["DB_NAME"] ? '$_SERVER["DB_NAME"]' : '"' . $db_config['name'] . '"') . ';

#TPL
// multipage templates
$tpl_config["catchers"] = ' . var_export($tpl_config['catchers'], true) . ';

// 301 redirects
$tpl_config["redirects"] = ' . var_export($tpl_config['redirects'], true) . ';

# USER LOGIN
$auth_config = [];

// table where your users are stored
$auth_config["table"] = "' . $auth_config['table'] . '";

// required fields when registering and updating
$auth_config["required"] = ' . var_export($auth_config['required'], true) . ';

// automated emails will be sent from this address
$from_email = "' . $_POST['from_email'] . '";
$auth_config["from_email"] = $from_email;

// hash passwords
$auth_config["hash_password"] = ' . json_encode($auth_config['hash_password']) . ';
$auth_config["hash_salt"] = "' . $auth_config['hash_salt'] . '";

// email activation
$auth_config["email_activation"] = ' . json_encode($auth_config['email_activation']) . ';

// use a secret term to encrypt cookies
$auth_config["secret_phrase"] = "' . $auth_config['secret_phrase'] . '";

// for use with session and cookie vars
$auth_config["cookie_prefix"] = "' . $auth_config['cookie_prefix'] . '";

// how long a cookie lasts with remember me
$auth_config["cookie_duration"] = "' . $auth_config['cookie_duration'] . '";

// Single Sign-on credentials
$auth_config["facebook_appId"] = "' . $auth_config['facebook_appId'] . '" ?: $_SERVER["facebook_appId"];
$auth_config["facebook_secret"] = "' . $auth_config['facebook_secret'] . '" ?: $_SERVER["facebook_secret"];

$auth_config["google_appId"] = "' . $auth_config['google_appId'] . '" ?: $_SERVER["google_appId"];
$auth_config["google_secret"] = "' . $auth_config['google_secret'] . '" ?: $_SERVER["google_secret"];

$auth_config["recaptcha_key"] = "' . $auth_config['recaptcha_key'] . '" ?: $_SERVER["recaptcha_key"];
$auth_config["recaptcha_secret"] = "' . $auth_config['recaptcha_secret'] . '" ?: $_SERVER["recaptcha_secret"];
$auth_config["recaptcha_threshold"] = "' . $auth_config['recaptcha_threshold'] . '" ?: "0.5";

# ADMIN AREA
// sections in admin navigation
$vars = ' . var_export($_POST['vars'], true) . ';

# SHOP
$shop_enabled = ' . str_to_bool($shop_enabled) . ';
$shop_config["paypal_email"] = "' . $shop_config['paypal_email'] . '";
$shop_config["include_vat"] = ' . str_to_bool($shop_config['include_vat']) . ';
';

			file_put_contents($config_file, $config);

			// clear config.php cache
			if (function_exists('opcache_reset')) {
				opcache_reset();
			}

			break;

		default:
			// get tables
			$tables = [];
			$rows = sql_query("SHOW TABLES FROM `" . escape($db_config['name']) . "`");
			foreach ($rows as $row) {
				$table = current($row);
				$section = spaced($table);

				$fields = sql_query("SHOW FULL COLUMNS FROM `" . escape($table) . "`");

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
			
			if (!$vars['menu']) {
            	foreach ($vars["sections"] as $section) {
            		$menu[] = [
				        'title' => ucfirst(spaced($section)),
				        'section' => $section,
				        'icon' => get_icon($section),
				    ];
            	}
				
				$vars['menu'] = $menu;
			}

			$response['tables'] = $tables;
			$response['vars'] = $vars;
			$response['from_email'] = $from_email;
			$response['last_modified'] = $last_modified;
			
			$response['version'] = cms::VERSION;
			
			$json = json_decode(wget('https://api.github.com/repos/adamjimenez/shiftlib/releases'), true);
			$response['latest'] = $json[0]['name'];
			break;
	}

} catch(Exception $e) {
	$response['error'] = $e->getMessage();
}

$response['success'] = $response['error'] ? false : true;

header('Content-Type: application/json, charset=utf-8');
print json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);