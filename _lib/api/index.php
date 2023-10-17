<?php
require('../base.php');

ini_set('auto_detect_line_endings', '1');

function get_items($value)
{
    global $options, $section, $field, $last_parent;
    
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

// check permissions
if (!$auth->user['admin'] and !$auth->user['privileges'][$_GET['section']]) {
    die('permission denied');
}

$response = [];

switch ($_GET['cmd']) {
    case 'ping':
    break;
    
    case 'logs':
        $offset = (int)$_GET['offset'] ?: 0;
        $length = (int)$_GET['length'] ?: 20;
        
        if ($_GET['section']) {
            $where_str = $_GET['id'] ? "item='" . escape($_GET['id']) . "' AND " : '';
            $where_str .= "section='" . escape($_GET['section']) . "'";
            
            $response['data'] = sql_query("SELECT *, L.date FROM cms_logs L
            	LEFT JOIN users U ON L.user=U.id
            	WHERE
            	    $where_str
            	ORDER BY L.id DESC
            	LIMIT " . $offset . ", " . $length . "
            ");
        }
    break;
    
    case 'autocomplete':

        $name = spaced($_GET['field']);
        
        if (!isset($vars['options'][$name])) {
            die('no options');
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
            $results[] = [
                'value' => $k,
                'label' => $v,
            ];
        }
        
        print json_encode($results);
        exit;
        
    break;
    
    case 'chained':

        $section = $_GET['section'];
        $value = (int) $_GET['value'];
        
        if (!isset($section) or !isset($value)) {
            die('missing fields');
        }
        
        $fields = $cms->get_fields($section);
        
        // get field
        $field = '';
        foreach ($fields as $k => $v) {
            $type = $v['type'];
            if ('separator' != $type) {
                $field = $k;
                break;
            }
        }
        
        if (!$field) {
            die('missing field');
        }
        
        $data = [];
        $options = [];
        
        if ($_GET['parent']) {
            get_items($value);
        } else {
            $parents = sql_query('SELECT id, ' . $field . ' FROM `' . escape(underscored($section)) . "`
                WHERE 
                    parent = '" . escape($value) . "'
            ");
            
            foreach ($parents as $v) {
                $options[$v['id']] = ['value' => $v[$field]];
            }
        }
        
        $response['options'] = $options;
        
        $response['success'] = true;
    
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
    
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }
    
    break;
    
    case 'reorder':

        foreach ($_POST['items'] as $v) {
            sql_query('UPDATE ' . escape(underscored($_GET['section'])) . " SET
                `position` = '" . escape($v['position']) . "'
                WHERE
                    id = '" . escape($v['id']) . "'
            ");
        }
        
        $response['success'] = true;
    
    break;
    
    case 'csv_upload':
        
        $file = current($_FILES);
        
        //check for an error
        $response['error'] = $file['error'];
    
        if ($response['error']) {
            break;
        }
    
        //check file is allowed
        if ('csv' !== file_ext($file['name'])) {
            $response['error'] = 'File size: 0 bytes';
        }
    
        if (0 == $file['size']) {
            $response['error'] = 'File size: 0 bytes';
        }
    
        if (!$response['error']) {
            if (!move_uploaded_file($file['tmp_name'], 'uploads/' . $_SERVER['HTTP_HOST'] . '.csv')) {
                $response['error'] = 'File upload error. Make sure folder is writeable.';
            }
        }
        
        if ($file['name']) {
            $response['file'] = $file['name'];
        } else {
            $response['error'] = 'no file';
        }
    break;
    
    case 'csv_fields':
        
        $handle = fopen('uploads/' . $_SERVER['HTTP_HOST'] . '.csv', 'r');
        if (false === $handle) {
            die('error opening ' . $_SERVER['HTTP_HOST'] . '.csv');
        }
        while (false !== ($data = fgetcsv($handle, 1000, ',')) and $i < 3) {
            for ($j = 0; $j < count($data); $j++) {
                if (0 == $i) {
                    if ($data[$j]) {
                        $options[$j] = $data[$j];
                    } else {
                        $options[$j] = 'Col ' . num2alpha($j + 1);
                    }
                }
                if (strstr($data[$j], '@')) {
                    $vars['email'] = $j;
                }
                $rows[$i][] = $data[$j];
            }
        
            if (0 == $i) {
                break;
            }
        
            $i++;
        }
        
        $response['options'] = $options;
    break;
    
    case 'csv_preview':
        $i = 0;
        
        $handle = fopen('uploads/' . $_SERVER['HTTP_HOST'] . '.csv', 'r');
        if (false === $handle) {
            die('error opening ' . $_SERVER['HTTP_HOST'] . '.csv');
        }
        while (false !== ($data = fgetcsv($handle, 1000, ',')) and $i < 3) {
            for ($j = 0; $j < count($data); $j++) {
                if (0 == $i) {
                    if ($data[$j]) {
                        $rows[$i][] = $data[$j];
                    } else {
                        $rows[$i][] = 'Col ' . num2alpha($j);
                    }
                } else {
                    $rows[$i][] = $data[$j];
                }
            }
            $i++;
        }
        
        $response['rows'] = $rows;
    break;
    
    case 'csv_import':
        if (!$_GET['csv']) {
            $errors[] = 'csv';
        }
        
        if (!$_GET['section']) {
            $errors[] = 'section';
        }
        
        if (!$errors) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            
            $cols = $_GET['fields'];
            $startedAt = time();
        
            $i = 0;
            $total = 0;
        
            $csv_path = 'uploads/' . $_SERVER['HTTP_HOST'] . '.csv';
        
            $handle = fopen($csv_path, 'r');
            if (false === $handle) {
                die('error opening ' . basename($csv_path));
            }
            
            $fields = $cms->get_fields($_GET['section']);
            
            while (false !== ($data = fgetcsv($handle, 0, ','))) {
                if (0 != $i) {
                    $row = [];
                    foreach ($cols as $k => $v) {
                        $row[$k] = $data[$v];
                    }
        
                    $i++;
        
                    $query = '';
                    $where = '';
                    $data = [];
        
                    //check dropdowns
                    foreach ($row as $k => $v) {
                        $field_name = underscored($k);
        
                        if ('related' == $k or 'position' == $k) {
                            continue;
                        } elseif ('select' === $fields[$k]['type']) {
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
        
                    //CMS SAVE
                    $cms->set_section($_GET['section'], $row_id, array_keys($data));
        
                    $result = 1;
                    if ($_GET['validate'] and count($cms->validate($data))) {
                        $result = 0;
                    } else {
                        if (!$row_id or $_GET['update']) {
                            $id = $cms->save($data);
                            
                            if ($id) {
                                if ($row_id) {
                                    $result = 2;
                                }
                            } else {
                                $result = 0;
                            }
                        }
                    }
        
                    send_msg($startedAt, $result);
                }
        
                $i++;
            }
        
            //delete file
            unlink($csv_path);
        }
    break;
    
    default:
        if (!$_GET['section']) {
            break;
        }
        
        // datatable search
        $_GET['fields']['s'] = $_GET['fields']['s'] ?: $_POST['search']['value'];
        
        $fields = $cms->get_fields($_GET['section']);
        
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
        }
        
        // add extra fields for checkbox and actions
        array_unshift($cols, 'id');
        
        // sort order
        if ($fields['position'] && $fields['position']['type'] === 'position') {
            $order = 'position';
        } else {
            $order = underscored($cols[($_POST['order'][0]['column'] - 2)]) ?: 'id';
        }
        $dir = ('desc' == $_POST['order'][0]['dir']) ? 'DESC' : '';
        $asc = ('desc' == $_POST['order'][0]['dir']) ? false : true;
        
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
            ' . $sql['having_str']
        , 1);
        
        $response = [
            'draw' => $_POST['draw'],
            'data' => [],
            'recordsTotal' => $count['count'],
            'recordsFiltered' => $count['count'],
        ];
        
        // get limit
        $limit = '';
        $start = (int) $_POST['start'];
        $length = (int) $_POST['length'];
        
        // prevent memory leak
        if ($length <=0) {
            $length = 500;
        }
        
        $limit = $start . ', ' . $length;
        
        // gather rows
        $rows = $cms->get($_GET['section'], $conditions, $limit, $order, $asc);
        //var_dump($rows);
        
        // prepare rows
        foreach ($rows as $row) {
            // every item starts with position
            $item = [$row['position'], $row['id']];
            
            // use labels when available
            foreach ($cols as $name) {
                $field_name = underscored($name);
                
                // truncate editor
                if (in_array($fields[$name]['type'], ['editor', 'textarea'])) {
                    $row[$field_name] = truncate($row[$field_name]);
                }
                
                // get field values
                if (in_array($fields[$name]['type'], ['select_multiple'])) {
                    $label_table = $vars['options'][spaced($name)];
                    
                    if (is_string($vars['options'][spaced($name)])) {
                        $label = $cms->get_label_field($label_table);
                        
                        foreach ($row[$field_name] as $k => $v) {
                            $result = cache_query("SELECT " . $label['column'] . " AS 'label' FROM `".escape($label_table)."`  WHERE id = '".(int)$v."'", 1);
                            $row[$field_name][$k] = is_array($result) ? current($result) : '';
                        }
                    }
                }
                
                $item[] = $row[$field_name . '_label'] ?: $row[$field_name];
            }
            
            $response['data'][] = $item;
        }
    break;
}

$response['success'] = $response['error'] ? false : true;

header('Content-Type: application/json, charset=utf-8');
print json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
