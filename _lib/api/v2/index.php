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
        if (1 !== $auth->user['admin'] && $auth->user['privileges'][$v['section']]) {
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

    // check permissions
    if (!$auth->user['admin'] && !$auth->user['privileges'][$_GET['section']] && !in_array($_GET['cmd'], ['login'])) {
        // check table exists
        if (!table_exists($auth->table)) {
            $cms->check_table($auth->table, $cms->default_users_table);
        }

        // check admin user exists
        $row = sql_query('SELECT id FROM ' . $auth->table . ' LIMIT 1', 1);
        if (!$row) {
            $default_pass = $auth->create_hash('123');

            sql_query('INSERT INTO ' . $auth->table . " SET email='admin', password='" . escape($default_pass) . "', admin='1'");
            
            $auth->set_login('admin', $default_pass);
            
            $auth->load();
        } else {
            header("HTTP/1.1 401 Unauthorized");
            throw new Exception('permission denied');
        }
    }

    switch ($_GET['cmd']) {
        case 'login':
            $data = $_POST;
            $data['login'] = 1;

            $response = $auth->login($data);
            break;
        case 'config':
            $data = [];
            
            if (!$vars['menu']) {
                $icons = [
        	        'users' => 'mdi-account',
        	        'orders' => 'mdi-basket',
        	        'pages' => 'mdi-file-document-edit',
        	        'settings' => 'mdi-cog',
        	        'enquiries' => 'mdi-email-box',
        	        'news' => 'mdi-newspaper',
        	    ];
                
            	foreach ($vars["sections"] as $section) {
            		$vars['menu'][] = [
				        'title' => ucfirst(spaced($section)),
				        'section' => $section,
				        'icon' => $icons[$section],
				    ];
            	}
            }
            
            $data['menu'] = $vars['menu'];
            
            $filters = sql_query("SELECT * FROM cms_filters WHERE user = '" . escape($auth->user['id']) . "'");

            $vars['menu'] = filter_menu($data['menu']);
            $vars['buttons'] = $cms->buttons;

            $response['vars'] = $vars;
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

        case 'autocomplete':
            $name = spaced($_GET['field']);

            if (!isset($vars['options'][$name])) {
                throw new Exception('no options');
            }

            $table = underscored($vars['options'][$name]);

            $fields = $cms->get_fields($vars['options'][$name]);

            foreach ($fields as $k => $v) {
                $type = $v['type'];
                if ('separator' != $type) {
                    $field = $k;
                    break;
                }
            }

            $cols .= '`' . underscored($field) . '`';

            $rows = sql_query("SELECT id, $cols FROM
                $table
                WHERE
                    `$field` LIKE '" . escape($_GET['term']) . "%'
                ORDER BY `" . underscored($field) . '`
                LIMIT 10
            ');

            $options = [];
            foreach ($rows as $row) {
                $options[$row['id']] = $row[underscored($field)];
            }

            $results = [];
            foreach ($options as $k => $v) {
                $response['options'][] = [
                    'value' => $k,
                    'title' => $v,
                ];
            }

            break;

        case 'rating':
            if ($_POST['section'] and $_POST['field'] and $_POST['item'] and $_POST['value']) {
                if (!table_exists('cms_ratings')) {
                    sql_query('CREATE TABLE IF NOT EXISTS `cms_ratings` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      `user` varchar(255) NOT NULL,
                      `section` varchar(255) NOT NULL,
                      `field` varchar(255) NOT NULL,
                      `item` int(11) NOT NULL,
                      `value` tinyint(4) NOT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `id` (`id`),
                      UNIQUE KEY `user_section_field_item` (`user`,`section`,`field`,`item`)
                    )');
                }

                $user = $auth->user['id'] ?: $_SERVER['REMOTE_ADDR'];

                sql_query("INSERT INTO cms_ratings SET
                    user = '" . $user . "',
                    section = '" . escape($_POST['section']) . "',
                    field = '" . escape($_POST['field']) . "',
                    item = '" . escape($_POST['item']) . "',
                    value = '" . escape($_POST['value']) . "'
                    ON DUPLICATE KEY UPDATE value = '" . escape($_POST['value']) . "'
                ");

                //get average
                $row = sql_query("SELECT AVG(value) AS `value` FROM cms_ratings
                    WHERE
                        section = '" . escape($_POST['section']) . "' AND
                        field = '" . escape($_POST['field']) . "' AND
                        item = '" . escape($_POST['item']) . "'
                ", 1);
                $value = round($row['value']);

                //update average
                sql_query('UPDATE ' . underscored(escape($_POST['section'])) . ' SET
                    `' . underscored(escape($_POST['field'])) . "` = '" . $value . "'
                    WHERE
                        id = '" . escape($_POST['item']) . "'
                    LIMIT 1
                ");

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

            $response['success'] = true;

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
            $path = 'uploads/';

            if ($_GET['path'] && !starts_with($_GET['path'], '../')) {
                $path .= $_GET['path'];
            }

            if ($_POST['createFolder']) {
                $result = mkdir($path . $_POST['createFolder']);
                
                if ($result === false) {
                    throw new Exception('error creating folder ' . $_POST['createFolder']);
                }
            } else if ($_FILES['file']) {
                $tmp = $_FILES['file']['tmp_name'];
                $file_name = $_FILES['file']['name'];
                $dest = $path . $file_name;

                if ($tmp) {
                    $result = rename($tmp, $dest);
                
                    if ($result === false) {
                        throw new Exception("Can't rename " . $tmp . ' to ' . $dest);
                    }
                }
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
                            $item['thumb'] = image($item['id'], 50, 39, false);
                        }

                        $items[] = $item;
                    }
                }

                $items = array_orderby($items, 'leaf');

                $response['items'] = $items;

            }

            break;

        case 'import':
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

            if (!$_POST['button']) {
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
                $sections = $vars['sections'];

                // get all tables
                $rows = sql_query("SHOW TABLES FROM `" . escape($db_config['name']) . "`");

                foreach ($rows as $row) {
                    $sections[] = spaced(current($row));
                }

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
            $cms->set_section($_GET['section'], $_GET['id']);
            $response['errors'] = $cms->validate($_POST, null, true);

            if (!count($response['errors'])) {
                $response['id'] = $cms->save($_POST['save']);
            }
            break;

        case 'restore':
            $cms->set_section($_GET['section'], $_POST['id'], ['deleted']);
            $cms->save(['deleted' => 0]);
            break;

        case 'bulkedit':
            foreach ($_POST['ids'] as $id) {
                $cms->set_section($_GET['section'], $id, array_keys($_POST['data']));
                $cms->save($_POST['data']);
            }
            break;

        case 'delete':
            $conditions = $_POST['select_all_pages'] ? $_GET : $_POST['ids'];
            $cms->delete_items($_POST['section'], $conditions, $_POST['select_all_pages']);
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
            // $order = in_array('position', $this->vars['fields'][$this->vars['options'][$name]]) ? 'position' : $field;
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

        case 'search':
            if (!$_GET['section']) {
                break;
            }

            // datatable search
            $_GET['fields']['s'] = $_POST['term'];

            $fields = $cms->get_fields($_GET['section']);

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
            }

            // add extra fields for checkbox and actions
            array_unshift($cols, 'id');

            // sort order
            if ($fields['position'] && $fields['position']['type'] === 'position') {
                $order = 'position';
            } else {
                $order = underscored($cols[($_POST['order'][0]['column'] - 2)]) ?: 'id';
            }

            $conditions = (array)$_GET['fields'];

            // restrict results to staff perms
            $cms->check_permissions();
            foreach ($auth->user['filters'][$_GET['section']] as $k => $v) {
                $conditions[$k] = $v;
            }

            $sql = $cms->conditionsToSql($_GET['section'], $conditions);

            // gather rows
            $rows = $cms->get($_GET['section'], $conditions, 10);
            //var_dump($rows);

            $label = $cms->get_label_field($_GET['section']);

            // prepare rows
            $response['data'] = [];
            foreach ($rows as $row) {
                $response['data'][] = [
                    'title' => $row[$label['column']],
                    'value' => $row['id'],
                ];
            }
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

            // datatable search
            $_GET['fields']['s'] = $_GET['fields']['s'] ?: $_POST['search']['value'];

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

            break;
    }

} catch(Exception $e) {
    $response['error'] = $e->getMessage();
}

$response['success'] = $response['error'] ? false : true;

print json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);