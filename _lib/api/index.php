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

function send_msg($id, $msg)
{
    echo "id: $id" . PHP_EOL;
    echo "data: {\n";
    echo "data: \"msg\": \"$msg\", \n";
    echo "data: \"id\": $id\n";
    echo "data: }\n";
    echo PHP_EOL;
    ob_flush();
    flush();
}

// check permissions
if (!$auth->user['admin'] and !$auth->user['privileges'][$_GET['section']]) {
    die('permission denied');
}

$response = [];

switch ($_GET['cmd']) {
    case 'autocomplete':

        $name = $_GET['field'];
        
        if (!isset($vars['options'][$name])) {
            die('no options');
        }
        
        $table = underscored($vars['options'][$name]);
        
        foreach ($vars['fields'][$vars['options'][$name]] as $k => $v) {
            if ('separator' != $v) {
                $field = $k;
                break;
            }
        }
        
        $raw_option = $vars['fields'][$vars['options'][$name]][$field];
        
        $cols = '';
        if (is_array($raw_option)) {
            $db_field_name = $this->db_field_name($vars['options'][$name], $field);
            $cols .= '' . underscored($db_field_name) . ' AS `' . underscored($field) . '`' . "\n";
        } else {
            $cols .= '`' . underscored($field) . '`';
        }
        
        $parent_field = array_search('parent', $vars['fields'][$vars['options'][$name]]);
    
        if (false !== $parent_field) {
            $options = $this->get_children($vars['options'][$name], $parent_field);
        } else {
            $rows = sql_query("SELECT id,$cols FROM
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
        
        // get field
        $field = '';
        foreach ($vars['fields'][$section] as $k => $v) {
            if ('separator' != $v) {
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
            
            $fields = $_GET['fields'];
            $startedAt = time();
        
            $i = 0;
            $total = 0;
        
            $csv_path = 'uploads/' . $_SERVER['HTTP_HOST'] . '.csv';
        
            $handle = fopen($csv_path, 'r');
            if (false === $handle) {
                die('error opening ' . basename($csv_path));
            }
            while (false !== ($data = fgetcsv($handle, 0, ','))) {
                if (0 != $i) {
                    $row = [];
                    foreach ($fields as $k => $v) {
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
                        } elseif ('select' == $vars['fields'][$_GET['section']][$k]) {
                            if (false === is_array($vars['options'][$k])) {
                                if (!$v) {
                                    $v = '';
                                } elseif (!is_numeric($v)) {
                                    reset($vars['fields'][$vars['options'][$k]]);
        
                                    $option_id = sql_query('SELECT id FROM `' . escape(underscored($vars['options'][$k])) . '` WHERE `' . underscored(key($vars['fields'][$vars['options'][$k]])) . "`='" . escape(trim($v)) . "'", true);
        
                                    if (count($option_id)) {
                                        $v = $option_id['id'];
                                    } else {
                                        //CMS SAVE
                                        $cms->set_section($vars['options'][$k]);
        
                                        $option_data = [
                                            key($vars['fields'][$vars['options'][$k]]) => trim($v),
                                        ];
        
                                        if (count($cms->validate($option_data))) {
                                            continue;
                                        }
                                        $v = $cms->save($option_data);
                                    }
                                } else {
                                    $v = $v;
                                }
                            } else {
                                if (is_assoc_array($vars['options'][$k])) {
                                    $v = key($vars['options'], $v);
                                } else {
                                    $v = $v;
                                }
                            }
                        } elseif ('select-multiple' == $vars['fields'][$_GET['section']][$k] or 'checkboxes' == $vars['fields'][$_GET['section']][$k]) {
                            $data[$field_name] = explode("\n", $v);
        
                            //trim data
                            foreach ($data[$field_name] as $key => $val) {
                                $data[$field_name][$key] = trim($val);
                            }
        
                            continue;
                        }
        
                        $v = trim($v);
        
                        if ($v) {
                            $data[$field_name] = $v;
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
                    $cms->set_section($_GET['section'], $row_id);
        
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
        
        $table = escape(underscored($_GET['section']));
        
        // get field names
        $fields = [];
        foreach ($vars['fields'][$_GET['section']] as $name => $type) {
            if (in_array($type, $cms->hidden_columns)) {
                continue;
            }
            
            $fields[] = $name;
        }
        
        // add extra fields for checkbox and actions
        array_unshift($fields, 'id');
        
        // sort order
        if (in_array('position', $vars['fields'][$_GET['section']])) {
            $order = 'position';
        } else {
            $order = underscored($fields[($_POST['order'][0]['column'] - 1)]) ?: 'id';
        }
        $dir = ('desc' == $_POST['order'][0]['dir']) ? 'DESC' : '';
    
        $sql = $cms->conditionsToSql($_GET['section'], $_GET['fields']);
        
        $count = sql_query('SELECT count(*) AS `count` FROM ' . $table . ' T_' . $table . '
            ' . $sql['joins'] . '
            ' . $sql['where_str'] . '
        ', 1);
        
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
        
        if (-1 != $length) {
            $limit = 'LIMIT ' . $start . ', ' . $length;
        }
        
        // gather rows
        $rows = sql_query("SELECT T_$table.* " . $sql['cols'] . " FROM $table T_$table
            " . $sql['joins'] . '
            ' . $sql['where_str'] . "
            GROUP BY
                T_$table.id
            ORDER BY
                T_$table.$order $dir
                $limit
        ");
        
        // prepare rows
        foreach ($rows as $row) {
            // every item starts with position
            $item = [$row['position']];
            
            // use labels when available
            foreach ($fields as $name) {
                if ('password' == $vars['fields'][$_GET['section']][$name]) {
                    continue;
                }
                
                $field_name = underscored($name);
                $item[] = $row[$field_name . '_label'] ?: $row[$field_name];
            }
            
            $response['data'][] = $item;
        }
    break;
}

$response['success'] = $response['error'] ? false : true;

header('Content-Type: application/json, charset=utf-8');
print json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
