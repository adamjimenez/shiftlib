<?php
require('../../base.php');
require(__DIR__ . "/../includes/cors.php");

ini_set('auto_detect_line_endings', '1');

// get json post
$request_body = file_get_contents('php://input');
if ($request_body) {
    $_POST = json_decode($request_body, true);
}

function get_items($value) {
    global $options,
    $section,
    $field,
    $last_parent;

    $item = sql_query('SELECT parent FROM `' . escape(underscored($section)) . "`
        WHERE
            id = '" . escape($value) . "'
    ", 1);

    $parent = $item['parent'];

    $parents = sql_query('SELECT id, ' . $field . ' FROM `' . escape(underscored($section)) . "`
        WHERE
            parent = '" . escape($parent) . "'
    ");

    $opts = [];
    foreach ($parents as $v) {
        $opts[$v['id']] = ['value' => $v[$field]];
    }

    if ($opts[$value]) {
        $opts[$value]['children'] = $options;
    }

    $options = $opts;

    if (0 != $parent) {
        get_items($parent);
    }
}

function filter_menu($arr) {
    global $cms,
    $auth,
    $filters;

    $new_arr = [];

    $i = 0;
    foreach ($arr as $k => $v) {
        if ($v['children']) {
            $v['children'] = filter_menu($v['children']);
        }

        if (!$v['section']) {
            $new_arr[$i] = $v;
            $i++;
            continue;
        }

        // check permissions
        if (1 !== (int)$auth->user['admin'] && !$auth->user['privileges'][$v['section']]) {
            continue;
        }

        try {
            $fields = $cms->get_fields($v['section']);

            if ($fields['read']) {
                $v['count'] = $cms->get($v['section'], ['read' => 0], true);
            }
        } catch (Exception $e) {}

        // fixme custom pages
        $v['to'] = file_exists('_tpl/admin/' . underscored($v['section']) . '.php') ? $v['section'] : 'section/' . $v['section'];

        $new_arr[$i] = $v;

        foreach ($filters as $k2 => $filter) {
            if ($filter['section'] != $v['section']) {
                continue;
            }

            parse_str($filter['filter'], $conditions);
            $result = $cms->get($filter['section'], $conditions, true);

            // backcompat
            if (starts_with($filter['filter'], 'option=')) {
                $filter['filter'] = substr($filter['filter'], 7);

                $pos = strpos($filter['filter'], '&');
                if ($pos !== false) {
                    $filter['filter'] = 'section/' . substr_replace($filter['filter'], '?', $pos, 1);
                }
            } else {
                $filter['filter'] = 'section/' . $filter['section'] . '?' . $filter['filter'];
            }
            
            if (!count((array)$new_arr[$i]['children'])) {
                $new_arr[$i]['children'][] = [
                    'icon' => 'mdi-filter',
                    'title' => 'All',
                    'to' => 'section/' . $filter['section'],
                ];
            }

            $new_arr[$i]['children'][] = [
                'icon' => 'mdi-filter',
                'title' => $filter['name'],
                'to' => $filter['filter'],
                'count' => $result,
                'filter_id' => $filter['id'],
            ];
        }
        
        $i++;
    }

    return array_values($new_arr);
}

header('Content-Type: application/json, charset=utf-8');
$response = [];

try {
        
    // check 2fa
    $trusted = null;
    if ($auth->user['admin']) {
        $trusted = $auth->check_2fa();
    }
    
    if (
        !in_array($_GET['cmd'], ['login', 'logout']) && (
            $trusted === false || !$auth->user['admin']
        )
    ) {
        // check table exists
        if (!table_exists($auth->table)) {
            $cms->check_table($auth->table, $cms->default_users_table);
        }

        header("HTTP/1.1 401 Unauthorized");
        throw new Exception('permission denied');
    }
    
    $cms->check_permissions();
    if ((int)$auth->user['admin'] !== 1 and $_GET['section'] and !$auth->user['privileges'][$_GET['section']]) {
        throw new Exception('access denied');
    }

    switch ($_GET['cmd']) {
        case 'login':
            // password reset
            if ($_POST['reset']) {
                if ($_POST['email']) {
                    $_POST['forgot_password'] = 1;
                }
                
                $response = $auth->forgot_password_handler([
                    'code' => $_POST['query']['code'],
                    'user' => $_POST['query']['user'],
                    'forgot_password' => $_POST['forgot_password'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                ], ['request' => 'admin/login']);
            } else {
                $row = sql_query('SELECT id FROM ' . $auth->table . ' WHERE admin = 1 LIMIT 1', 1);
                
                if (!$row) {
                    if ($_POST['email'] && $_POST['password']) {
                        $hash = $auth->create_hash($_POST['password']);
                        
                        sql_query('INSERT INTO ' . $auth->table . " SET
                            email = '".escape($_POST['email'])."',
                            password = '".escape($hash)."',
                            admin = 1
                        ");
                        
                        $response = $auth->login_handler($_POST);
                    } else {
                        $response['no_admin'] = true;
                    }
                }
            
                if (!$auth->user && $_POST['email']) {
                    $_POST['login'] = 1;
                    $response = $auth->login_handler($_POST);
                }
            }

            if ($auth->user) {
                $response['admin'] = (int)$auth->user['admin'];
                $response['code'] = 1;
                
                // 2fa
                if ($trusted === false) {
                    if ($_POST['otp']) {
                        $_SESSION['otp_attempts']++;
                
                        if ($_SESSION['otp_attempts'] > 3) {
                            throw new Exception('Too many otp attempts');
                        } else {
                            if (strtoupper($_POST['otp']) !== $_SESSION['otp']) {
                                throw new Exception('OTP is incorrect');
                            }
                            
                            $auth->pass_2fa();
                            $trusted = true;
                        }
                    } else if ($_POST['otp_method']) {
                        $auth->send_2fa();
                    }
                    
                    $response['trusted'] = $trusted;
                    $response['email'] = anonymize_email($auth->user['email']);
                    $response['otp_sent'] = $_SESSION['otp'] != '';
                }
            }
            break;
        case 'logout':
            $auth->logout(false);
            break;
        case 'config':
            $data = [];
            
            if (!$vars['menu']) {
            	foreach ($vars["sections"] as $section) {
            		$vars['menu'][] = [
				        'title' => ucfirst(spaced($section)),
				        'section' => $section,
				        'icon' => get_icon($section),
				    ];
            	}
            }
            
            $data['menu'] = $vars['menu'];
            
            if (table_exists('cms_filters')) {
                $filters = sql_query("SELECT * FROM cms_filters WHERE user = '" . escape($auth->user['id']) . "'");
            }

            $vars['menu'] = filter_menu($data['menu']);
            $vars['buttons'] = $cms->buttons;
            
            // reports
            if (table_exists('cms_reports')) {
                $vars['reports'] = sql_query("SELECT id, title FROM cms_reports WHERE user = '" . escape($auth->user['id']) . "'");
            }

            $response['vars'] = $vars;
            
            // user details
            $name = $auth->user['name'] ?: $auth->user['email'];
            $words = preg_split('/\s+/', $name);
            $initials = "";
            foreach ($words as $word) {
                $initials .= strtoupper($word[0]);
            }
            
            $response['user'] = [
                'name' => $name,
                'initials' => substr($initials, 0, 2),
                'admin' => (int)$auth->user['admin'],
                'privileges' => $auth->user['privileges'],
            ];
            break;

        case 'logs':
            if ($_GET['section']) {
                $where_str = $_GET['id'] ? "item='" . escape($_GET['id']) . "' AND " : '';
                $where_str .= "section='" . escape($_GET['section']) . "'";

                $response['data'] = sql_query("SELECT CONCAT(U.name, ' ', U.surname) AS `name`, L.user, L.task, L.date, L.details FROM cms_logs L
                	LEFT JOIN users U ON L.user=U.id
                	WHERE
                	    $where_str
                	ORDER BY L.id DESC
                ");
            }
            break;

        case 'reports':
            if ($_POST['save']) {
                $cms->set_section('cms_reports', (int)$_POST['id'], ['title', 'report', 'user']);
                $response['id'] = $cms->save([
                    'title' => $_POST['title'] ?: 'untitled',
                    'report' => $_POST['report'],
                    'user' => $auth->user['id'],
                ]);
            }  else if ($_POST['delete']) {
                $cms->delete_items('cms_reports', $_POST['delete']);
            } else if ($_GET['id']) {
                $response['report'] = sql_query("SELECT * FROM cms_reports WHERE id = '".(int)$_GET['id']."'", 1);
            }
            
            break;
            
        // used by combos
        case 'autocomplete':
            $name = spaced($_GET['field']);

            if (!isset($vars['options'][$name])) {
                throw new Exception('no options');
            }

            $table = underscored($vars['options'][$name]);
            $fields = $cms->get_fields($vars['options'][$name]);
            $field = array_key_first($fields);

            $rows = sql_query("SELECT id, `" . underscored($field) . "` FROM
                $table
                WHERE
                    `$field` LIKE '" . escape($_GET['term']) . "%'
                ORDER BY `" . underscored($field) . '`
                LIMIT 10
            ');

            $results = [];
            foreach ($rows as $row) {
                $response['options'][] = [
                    'value' => $row['id'],
                    'title' => $row[$field],
                ];
            }

            break;

        case 'reorder':
            foreach ($_POST['items'] as $k => $v) {
                sql_query('UPDATE ' . escape(underscored($_GET['section'])) . " SET
                    `position` = '" . escape($k + 1) . "'
                    WHERE
                        id = '" . escape($v['id']) . "'
                ");
            }

            break;

        case 'filters':
            if ($_POST['delete']) {
                sql_query("DELETE FROM cms_filters WHERE
                    id = '" . (int)$_POST['delete'] . "' AND
                    user = '" . (int)$auth->user['id'] . "'
                ");
            }
            if ($_POST['save']) {
                sql_query("INSERT INTO cms_filters SET
                    user = '" . (int)$auth->user['id'] . "',
                    section = '" . escape($_POST['section']) . "',
                    name = '" . escape($_POST['label']) . "',
                    filter = '" . escape(http_build_query($_POST['conditions'])) . "'
                ");
            }
            break;

        case 'uploads':
            if (1 !== (int)$auth->user['admin'] && 2 > (int)$auth->user['privileges']['uploads']) {
                throw new Exception('access denied');               
            }
            
            $path = 'uploads/';
            $subdir = $_GET['path'];

            if ($subdir && !starts_with($subdir, '../')) {
                $path .= $subdir . '/';
            }

            if ($_POST['createFolder']) {
                $result = mkdir($path . $_POST['createFolder']);
                
                if ($result === false) {
                    throw new Exception('error creating folder ' . $_POST['createFolder']);
                }
            } else if (count($_FILES)) {
                $file = current($_FILES);
                
                $tmp = $file['tmp_name'];
                $file_name = $file['name'];
                $dest = $path . $file_name;

                if ($tmp) {
                    $result = rename($tmp, $dest);
                    
                    // set perms
                    chmod($dest, 0777);
                
                    if ($result === false) {
                        throw new Exception("Can't rename " . $tmp . ' to ' . $dest);
                    }
                }
                
                $response['file'] = [
                    'url' => 'https://' . $_SERVER['HTTP_HOST'] . '/' . $path . $file_name
                ];
            } else if ($_POST['delete']) {
                foreach ((array)$_POST['delete'] as $v) {
                    if (!starts_with($v, '../')) {
                        $item_path = $path . $v;

                        if (is_file($item_path)) {
                            $result = unlink($item_path);
                
                            if ($result === false) {
                                throw new Exception('could not delete file ' . $item_path);
                            }
                        } else if (is_dir($item_path)) {
                            $result = rmdir($item_path);
                
                            if ($result === false) {
                                throw new Exception('could not delete directory ' . $item_path);
                            }
                        }
                    }
                }
            } else {
                $paths = glob($path . '*', GLOB_NOSORT);

                $items = [];
                foreach ($paths as $pathname) {
                    $basename = basename($pathname);
                    if ('.' != $basename && '..' != $basename) {
                        $item = [];
                        $item['id'] = substr($pathname, strlen('uploads/'));
                        $item['name'] = $basename;
                        $item['leaf'] = !is_dir($pathname);

                        if ($item['leaf']) {
                            $item['thumb'] = image($item['id'], 80, 60, false);
                        }

                        $items[] = $item;
                    }
                }

                $items = array_orderby($items, 'leaf');

                $response['items'] = $items;
            }

            break;

        case 'import':
            if (1 !== (int)$auth->user['admin'] && 2 > (int)$auth->user['privileges'][$_GET['section']]) {
                throw new Exception('access denied');               
            }
            
            $file = current($_FILES);
                
            if (!$file) {
                throw new Exception('missing file');
            }

            if (!$_GET['section']) {
                throw new Exception('missing section');
            }

            if (!is_array($_POST['fields'])) {
                throw new Exception('missing fields');
            }

            $cols = $_POST['fields'];
            $startedAt = time();

            $i = 0;
            $total = 0;

            $csv_path = $file['tmp_name'];

            $handle = fopen($csv_path, 'r');
            if (false === $handle) {
                throw new Exception('Error opening ' . basename($csv_path));
            }

            $fields = $cms->get_fields($_GET['section']);

            while (false !== ($data = fgetcsv($handle, 0, ','))) {
                $i++;

                if ($i == 1) {
                    continue;
                }

                $row = [];
                foreach ($cols as $k => $v) {
                    $row[$k] = $data[$v];
                }

                $query = '';
                $where = '';
                $data = [];

                //check dropdowns
                foreach ($row as $k => $v) {
                    $field_name = underscored($k);

                    if ('select' === $fields[$k]['type']) {
                        if (false === is_array($vars['options'][$k])) {
                            if (!$v) {
                                $v = '';
                            }
                        } else {
                            if (is_assoc_array($vars['options'][$k])) {
                                $v = key($vars['options'], $v);
                            } else {
                                $v = $v;
                            }
                        }
                    }

                    $v = trim($v);
                    $data[$field_name] = $v;

                    if ($v) {
                        $query .= "`$field_name`='" . escape($v) . "',\n";
                        $where .= "`$field_name`='" . escape($v) . "' AND\n";
                    }
                }

                if (!$where) {
                    continue;
                }

                $query = substr($query, 0, -2);
                $where = substr($where, 0, -4);

                if ($data['id']) {
                    $row_id = $data['id'];
                    $_GET['update'] = 1;
                } else {
                    $qry = 'SELECT id FROM `' . underscored(escape($_GET['section'])) . "` WHERE
                        $where
                        LIMIT 1
                    ";

                    $row = sql_query($qry, 1);
                    $row_id = $row['id'];
                }

                // CMS SAVE
                $cms->set_section($_GET['section'], $row_id, array_keys($data));

                $result = 1;
                if (!$row_id || $_GET['update']) {
                    $id = $cms->save($data);

                    if ($id) {
                        if ($row_id) {
                            $result = 2;
                            
                            $response['updated']++;
                        } else {
                            $response['inserted']++;
                        }
                    } else {
                        $response['skipped']++;
                        $result = 0;
                    }
                }

                //send_msg($startedAt, $result);

                $i++;
            }
                
            break;

        case 'file':
            $cms->file($_GET['f']);
            exit;
            break;

        case 'button':
            if (!$_GET['section']) {
                throw new Exception('missing section');
            }

            if (!isset($_POST['button'])) {
                throw new Exception('missing button');
            }

            $button = $cms->buttons[$_POST['button']];

            if (!$button) {
                throw new Exception('button not found');
            }

            if ($button['section'] !== $_GET['section']) {
                throw new Exception('wrong section');
            }

            $items = [];
            if ($_POST['select_all_pages']) {
                $items = $cms->get($_GET['section'], $_POST['search']);
            } elseif (is_array($_POST['ids'])) {
                $items = [];
                foreach ($_POST['ids'] as $v) {
                    $items[] = $cms->get($_GET['section'], $v);
                }
            }

            if (!count($items)) {
                throw new Exception('no items');
            }

            if (is_callable($button['handler'])) {
                $response['result'] = $button['handler']($button['page'] === 'view' ? $items[0] : $items);
            }

            break;

        case 'privileges':
            $has_privileges = false;
            if ((int)$auth->user['admin'] !== 1 && $auth->user['privileges']['cms privileges'] <= 1) {
                throw new Exception('access denied');
            }

            if (!$_GET['user_id']) {
                throw new Exception('missing user id');
            }

            $user = sql_query("SELECT id FROM users WHERE id = '".(int)$_GET['user_id']."' AND admin > 0", 1);
            if (!$user) {
                throw new Exception('staff user not found');
            }

            if ($_POST['privileges']) {
                sql_query("DELETE FROM cms_privileges WHERE user='" . (int)$user['id'] . "'");

                foreach ($_POST['privileges'] as $k => $v) {
                    sql_query("INSERT INTO cms_privileges SET
            			user='" . escape($user['id']) . "',
            			section='" . escape($k) . "',
            			access='" . escape($v['access']) . "',
            			filter='" . escape($v['filters']) . "'
            		");
                }
            } else {
                // get all tables
                $rows = sql_query("SHOW TABLES FROM `" . escape($db_config['name']) . "`");

                foreach ($rows as $row) {
                    if (in_array(current($row), ['cms_activation', 'cms_filters', 'cms_login_attempts', 'cms_logs', 'cms_privileges', 'files'])) {
                        continue;
                    }
                    
                    $sections[] = spaced(current($row));
                }

                $sections[] = 'uploads';
                $sections = array_unique($sections);
                sort($sections);

                // get user perms
                $rows = sql_query("SELECT * FROM cms_privileges WHERE user='" . (int)$user['id'] . "'");

                $perms = [];
                foreach ($rows as $row) {
                    $row['access'] = (int)$row['access'];
                    $perms[$row['section']] = $row;
                }

                $privileges = [];
                foreach ($sections as $section) {
                    $privileges[$section] = $perms[$section] ?: ['section' => $section];
                }

                $response['sections'] = $sections;
                $response['privileges'] = $privileges;
            }

            break;

        case 'save':
            if (1 !== (int)$auth->user['admin'] && 2 > (int)$auth->user['privileges'][$_GET['section']]) {
                throw new Exception('access denied');               
            }
            
            $fields = $_GET['fields'] ?: null;
            
            $cms->set_section($_GET['section'], $_GET['id'], $fields);
            $response['errors'] = $cms->validate($_POST, null, true);

            if (!count($response['errors'])) {
                $response['id'] = $cms->save($_POST);
            }
            break;

        case 'restore':
            if (1 !== (int)$auth->user['admin'] && 2 > (int)$auth->user['privileges'][$_GET['section']]) {
                throw new Exception('access denied');               
            }
            
            $cms->set_section($_GET['section'], $_POST['id'], ['deleted']);
            $cms->save(['deleted' => 0]);
            break;

        case 'bulkedit':
            if (1 !== (int)$auth->user['admin'] && 2 > (int)$auth->user['privileges'][$_GET['section']]) {
                throw new Exception('access denied');               
            }
            
            foreach ($_POST['ids'] as $id) {
                $cms->set_section($_GET['section'], $id, array_keys($_POST['data']));
                $cms->save($_POST['data']);
            }
            break;

        case 'delete':
            if (1 !== (int)$auth->user['admin'] && 2 > (int)$auth->user['privileges'][$_GET['section']]) {
                throw new Exception('access denied');               
            }
            
            $conditions = $_POST['select_all_pages'] ? $_GET : $_POST['ids'];
            $cms->delete_items($_GET['section'], $conditions, $_POST['select_all_pages']);
            break;

        case 'export':
            $fields = $cms->get_fields($_GET['section']);

            // convert json column indexes into array of column names
            $indexes = $_GET['columns'] ?: [];

            $columns = [];
            $i = 0;
            foreach ($fields as $name => $field) {
                $type = $field['type'];

                if (in_array($type, $cms->hidden_columns)) {
                    continue;
                }

                if ($indexes[$i]) {
                    $columns[] = $name;
                }
                $i++;
            }

            $conditions = (array)$_GET['fields'];
            $cms->export_items($_GET['section'], $conditions, true, $columns);
            break;

        case 'options':
            // get section table name
            $table = underscored($_GET['table']);

            // get first field from section as we will use this for the option labels
            $field = $cms->get_label_field($table);

            $cols = '`' . $field['column'] . '`';

            // sort by position if available or fall back to field order
            $order = $field['column'];

            // filter deleted rows
            $fields = $cms->get_fields($vars['options'][$name]);
            if ($fields['deleted']) {
                if ($where) {
                    $where .= ' AND ';
                }

                $where .= 'deleted = 0';
            }

            $whereStr = '';
            if ($where) {
                $whereStr = 'WHERE ' . $where;
            }

            $rows = sql_query("SELECT id, $cols FROM
                $table
                $whereStr
                ORDER BY `" . underscored($order) . '`
            ');

            $options = [];
            foreach ($rows as $row) {
                $options[$row['id']] = $row[$field['column']];
            }

            $response['options'] = $options;
            break;

        default:
            if (!$_GET['section']) {
                break;
            }

            $response['fields'] = $cms->get_fields($_GET['section']);

            // view page
            if (isset($_POST['id'])) {
                if ($_POST['id']) {
                    $response['data'] = $cms->get($_GET['section'], $_POST['id']);
                }
                break;
            }

            $fields = $response['fields'];

            $table = escape(underscored($_GET['section']));
            $field_id = 'id';

            if (!$fields['id']) {
                break;
            }

            // get field names
            $cols = [];
            foreach ($fields as $name => $field) {
                if (in_array($field['type'], $cms->hidden_columns)) {
                    continue;
                }

                $cols[] = $name;

                if ($_GET['parentsection'] && $_GET['parentsection'] === $vars['options'][$name]) {
                    $_GET['fields'][$name] = $_GET['parentid'];
                }
            }

            // add extra fields for checkbox and actions
            array_unshift($cols, 'id');

            // sort order
            if ($fields['position'] && $fields['position']['type'] === 'position') {
                $order = 'position';
            } else {
                $order = $_GET['sortBy'][0]['key'] ?: 'id';
            }
            $asc = ('desc' == $_GET['sortBy'][0]['order']) ? false : true;

            $conditions = (array)$_GET['fields'];

            // restrict results to staff perms
            $cms->check_permissions();
            foreach ($auth->user['filters'][$_GET['section']] as $k => $v) {
                $conditions[$k] = $v;
            }

            $sql = $cms->conditionsToSql($_GET['section'], $conditions);

            $count = sql_query('SELECT COUNT(DISTINCT T_' . $table . '.' . $field_id . ') AS `count` FROM `' . $table . '` T_' . $table . '
            ' . $sql['joins'] . '
            ' . $sql['where_str'] . '
            ' . $sql['having_str'], 1);

            $response['total'] = (int)$count['count'];

            $response['data'] = [];

            // get limit
            $limit = '';
            $length = (int)$_GET['itemsPerPage'] ?: 500;
            $start = max((int)($_GET['page'] - 1) * $length, 0);

            $limit = $start . ', ' . $length;
            
            if (is_array($_GET['columns']) && !in_array('id', $_GET['columns'])) {
                $_GET['columns'][] = 'id';
            }

            // gather rows
            $opts = [
                'section' => $_GET['section'],
                'conditions' => $conditions,
                'limit' => $limit,
                'order' => $order,
                'asc' => $asc,
                'columns' => $_GET['columns'],
            ];
            
            $response['data'] = $cms->get($opts);
            
            // compare
            if ($_GET['compare']) {
                $conditions = array_merge($conditions, $_GET['compare']);
                
                $opts = [
                    'section' => $_GET['section'],
                    'conditions' => $conditions,
                    'limit' => $limit,
                    'order' => $order,
                    'asc' => $asc,
                    'columns' => $_GET['columns'],
                ];
                
                $response['compare_data'] = $cms->get($opts);
            }

            break;
    }

} catch(Exception $e) {
    $response['error'] = $e->getMessage();
}

$response['success'] = $response['error'] ? false : true;

print json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);