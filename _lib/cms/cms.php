<?php
class cms
{
    public function cms()
    {
        global $cms_buttons, $vars;
        
        $this->language = 'en';
        
        // rating widget options
        $this->opts['rating'] = [
            1 => 'Very Poor',
            2 => 'Poor',
            3 => 'Average',
            4 => 'Good',
            5 => 'Excellent',
        ];

        //built in extensions
        $cms_buttons[] = [
            'section' => 'email templates',
            'page' => 'view',
            'label' => 'Send Preview',
            'handler' => function () {
                global $auth, $cms;
            
                $content = $cms->get('email templates', $_GET['id']);
                email_template($auth->user['email'], $content['id'], $auth->user);
                $_SESSION['message'] = 'Preview sent';
            },
        ];

        if (!$vars['fields']['email templates']) {
            $vars['fields']['email templates'] = [
                'subject' => 'text',
                'body' => 'textarea',
                'id' => 'id',
            ];
        }
    }
    
    public function check_table($table, $fields)
    {
        $select = sql_query("SHOW TABLES LIKE '$table'");
        if (!$select) {
            //build table query
            $query = '';
            foreach ($fields as $name => $type) {
                $name = underscored(trim($name));
    
                $db_field = $this->form_to_db($type);
    
                if ($db_field) {
                    $query .= '`' . $name . '` ' . $db_field . ' NOT NULL,';
                }
            }
    
            sql_query("CREATE TABLE `$table` (
                   `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
                   $query
                   PRIMARY KEY ( `id` )
                )
            ");
        }
    }
    
    public function form_to_db($type): string
    {
        switch ($type) {
            case 'id':
            case 'checkboxes':
            case 'separator':
            break;
            case 'textarea':
            case 'editor':
            case 'files':
            case 'phpuploads':
                return 'TEXT';
            break;
            case 'read':
            case 'deleted':
            case 'checkbox':
            case 'rating':
                return 'TINYINT';
            break;
            case 'int':
            case 'parent':
            case 'position':
            case 'translated-from':
                return 'INT';
            break;
            case 'datetime':
                return 'DATETIME';
            break;
            case 'timestamp':
                return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            break;
            case 'date':
            case 'dob':
            case 'month':
                return 'DATE';
            break;
            case 'time':
                return 'TIME';
            break;
            case 'polygon':
                return 'POLYGON';
            break;
            case 'coords':
                return 'POINT';
            break;
            case 'language':
                return "VARCHAR( 32 ) NOT NULL DEFAULT ''";
            break;
            case 'select':
            case 'radio':
            case 'combo':
                return "VARCHAR( 64 ) NOT NULL DEFAULT ''";
            break;
            case 'color':
                return "VARCHAR( 7 ) NOT NULL DEFAULT ''";
            break;
            default:
                return "VARCHAR( 140 ) NOT NULL DEFAULT ''";
            break;
        }
    }

    // export items to csv
    public function export_items($section, $items)
    {
        global $vars;
        
        foreach ($items as $item) {
            $vars['content'][] = $this->get($section, $item);
        }
    
        $i = 0;
        foreach ($vars['content'] as $row) {
            if (0 == $i) {
                $j = 0;
                foreach ($row as $k => $v) {
                    $data .= '"' . spaced($k) . '",';
                    $headings[$j] = $k;
                }
                $data = substr($data, 0, -1);
                $data .= "\n";
                $j++;
            }
            $j = 0;
            foreach ($row as $k => $v) {
                $data .= '"' . str_replace('"', '', $v) . '",';
                $j++;
            }
            $data = substr($data, 0, -1);
            $data .= "\n";
            $i++;
        }
    
        header('Pragma: cache');
        header('Content-Type: text/comma-separated-values; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $section . '.csv"');
        print($data);
        exit;
    }

    public function delete_items($section, $items) // used in admin system
    {
        global $auth;

        if (!is_array($items)) {
            $items = [$items];
        }

        if (1 == $auth->user['admin'] or 2 == $auth->user['privileges'][$section]) {
            $response = $this->delete($section, $items);

            if (false !== $response) {
                $_SESSION['message'] = 'The items have been deleted';
                return;
            }
        }
        
        $_SESSION['message'] = 'Permission denied.';
        return;
    }

    public function delete_all_pages($section, $conditions) // used in admin system
    {
        global $auth;

        if (1 == $auth->user['admin'] or 2 == $auth->user['privileges'][$section]) {
            $rows = $this->get($section, $conditions);

            $items = [];
            foreach ($rows as $v) {
                $items[] = $v['id'];
            }

            $this->delete($section, $items);

            $_SESSION['message'] = 'The items have been deleted';
        } else {
            $_SESSION['message'] = 'Permission denied, you have read-only access.';
        }
    }

    public function delete($section, $ids) // no security checks
    {
        global $vars;

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $field_id = $this->get_id_field($section);

        $response = $this->trigger_event('beforeDelete', [$ids]);

        if (false === $response) {
            return false;
        }
        
        $has_languages = in_array('language', $vars['fields'][$section]);
        
        foreach ($ids as $id) {
            // cheeck perms
            $conditions[$field_id] = $id;
            $content = $this->get($section, $conditions);
            if (!$content) {
                continue;
            }
            
            if (in_array('deleted', spaced($vars['fields'][$section]))) {
                $this->set_section($section, $id, ['deleted']);
                $this->save(['deleted' => 1]);
    
                if ($has_languages) {
                    sql_query('UPDATE `' . escape(underscored($section)) . "` SET
                        deleted = 1
                        WHERE
                            translated_from='$id'
                    ");
                }
            } else {
                sql_query('DELETE FROM `' . escape(underscored($section)) . '`
                    WHERE ' . $field_id . "='$id'
                        LIMIT 1
                ");
    
                if ($has_languages) {
                    sql_query('DELETE FROM `' . escape(underscored($section)) . "`
                        WHERE
                            translated_from='$id'
                    ");
                }
    
                //multiple select items
                sql_query("DELETE FROM cms_multiple_select
                    WHERE
                        section = '" . escape(underscored($section)) . "' AND
                        item = '$id'
                ");
            }
        }

        $this->trigger_event('delete', [$ids]);
    }

    // create search params, used by get()
    public function conditions_to_sql($section, $conditions = [], $num_results = null, $cols = null)
    {
        global $vars, $auth;

        $table = underscored($section);
        $field_id = $this->get_id_field($section);
        $has_languages = in_array('language', $vars['fields'][$section]);

        // check for id or page name
        $id = null;
        if (is_numeric($conditions)) {
            if ('en' == $this->language) {
                $id = $conditions;
            } else {
                $conditions = ['translated_from' => $conditions, 'language' => $this->language];
            }
            $num_results = 1;
        } elseif (is_string($conditions)) {
            if (in_array('page-name', $vars['fields'][$section])) {
                $field_page_name = array_search('page-name', $vars['fields'][$section]);
                $conditions = [$field_page_name => $conditions];
                $num_results = 1;
            }
        } else {
            if ($has_languages) {
                $conditions['language'] = $this->language;
            } elseif (!in_array('id', $vars['fields'][$section])) {
                $id = 1;
            }
        }

        if (is_numeric($id)) {
            $conditions = ['id' => $id];
        }
        
        // restrict results to staff perms
        foreach ($auth->user['filters'][$this->section] as $k => $v) {
            $conditions[$k] = $v;
        }
        
        // filter deleted
        if (in_array('deleted', $vars['fields'][$section]) and !isset($conditions['deleted']) and !$id) {
            $where[] = "T_$table.deleted = 0";
        }

        // filter other translations
        if ($has_languages and 'en' == $this->language) {
            $where[] = "`translated_from`='0'";
        }

        foreach ($vars['fields'][$section] as $name => $type) {
            $field_name = underscored($name);

            if (
                (isset($conditions[$name])) or
                (isset($conditions[$field_name]))
            ) {
                $value = $conditions[$name] ?: $conditions[$field_name];
                $operator = in_array($conditions['func'][$field_name], ['!=']) ? $conditions['func'][$field_name] :  'LIKE';

                switch ($type) {
                    case 'select':
                    case 'combo':
                    case 'radio':
                        if (is_array($value)) {
                            $or = '(';
                            foreach ($conditions[$field_name] as $k => $v) {
                                $or .= "T_$table." . $field_name . ' ' . $operator . " '" . escape($v) . "' OR ";
                            }
                            $or = substr($or, 0, -4);
                            $or .= ')';

                            $where[] = $or;
                        } else {
                            $where[] = "T_$table." . $field_name . ' ' . $operator . " '" . escape($value) . "'";
                        }
                    break;
                    case 'checkboxes':
                        if (1 == count($conditions[$field_name]) and !reset($conditions[$field_name])) {
                            $conditions[$field_name] = [$conditions[$field_name]];
                        }

                        $joins .= ' LEFT JOIN cms_multiple_select T_' . $field_name . ' ON T_' . $field_name . '.item=T_' . $table . '.' . $field_id;

                        $or = '(';

                        foreach ($conditions[$field_name] as $k => $v) {
                            $v = is_array($v) ? $v['value'] : $v;
                            $or .= 'T_' . $field_name . ".value = '" . escape($v) . "' AND T_" . $field_name . ".field = '" . escape($name) . "' OR ";
                        }
                        $or = substr($or, 0, -4);
                        $or .= ')';

                        $where[] = $or;
                        $where[] = 'T_' . $field_name . ".section='" . $section . "'";
                    break;
                    case 'date':
                    case 'datetime':
                    case 'timestamp':
                    case 'month':
                        if (!$conditions['func'][$field_name]) {
                            $conditions['func'][$field_name] = '=';
                        }
                        
                        if ('now' == $value) {
                            $start = 'NOW()';
                        } elseif ('month' == $conditions['func'][$field_name]) {
                            $start = dateformat('mY', $value);
                        } else {
                            $start = "'" . escape(dateformat('Y-m-d', $value)) . "'";
                        }

                        if ('month' == $conditions['func'][$field_name]) {
                            $where[] = 'date_format(T_' . $table . '.' . $field_name . ", '%m%Y') = '" . escape($value) . "'";
                        } elseif ('year' == $conditions['func'][$field_name]) {
                            $where[] = 'date_format(T_' . $table . '.' . $field_name . ", '%Y') = '" . escape($value) . "'";
                        } elseif ($conditions[$field_name] and $conditions['end'][$field_name]) {
                            $end = escape($conditions['end'][$field_name]);
                            
                            $where[] = '(T_' . $table . ".$field_name >= " . $start . ' AND T_' . $table . ".$field_name <= '" . $end . "')";
                        } elseif ($conditions['func'][$field_name]) {
                            $where[] = "T_$table." . $field_name . ' ' . escape($conditions['func'][$field_name]) . ' ' . $start;
                        }
                    break;
                    case 'time':
                    break;
                    case 'dob':
                        $where[] = '`' . $field_name . "`!='0000-00-00'";
                        $where[] = "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(" . $field_name . ", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(" . $field_name . ", '00-%m-%d')) ".$operator." " . escape($value) . ' ';
                    break;
                    case 'postcode':
                        if (calc_grids($value) and is_numeric($conditions['func'][$field_name])) {
                            $grids = calc_grids($value);

                            if ($grids) {
                                $cols .= ",
                                (
                                    SELECT
                                        ROUND(SQRT(POW(Grid_N-' . $grids[0] . ',2)+POW(Grid_E-" . $grids[1] . ",2)) * 0.000621371192)
                                        AS distance
                                    FROM postcodes
                                    WHERE
                                        Pcode=
                                        REPLACE(SUBSTRING(SUBSTRING_INDEX(T_$table.$field_name, ' ', 1), LENGTH(SUBSTRING_INDEX(T_$table.$field_name, ' ', 0)) + 1), ',', '')

                                ) AS distance";


                                $having[] = 'distance <= ' . escape($conditions['func'][$field_name]) . '';

                                $vars['labels'][$section][] = 'distance';
                            }
                        }
                    break;
                    case 'int':
                    case 'decimal':
                    case 'position':
                        $pos = strrpos($value, '-');

                        if ($conditions['func'][$field_name]) {
                            $where[] = "T_$table." . $field_name . ' ' . escape($conditions['func'][$field_name]) . " '" . escape($value) . "'";
                        } elseif ($pos > 0) {
                            $min = substr($value, 0, $pos);
                            $max = substr($value, $pos + 1);

                            $where[] = "(
                                T_$table." . $field_name . " >= '" . escape($min) . "' AND
                                T_$table." . $field_name . " <= '" . escape($max) . "'
                            )";
                        } else {
                            $where[] = "T_$table." . $field_name . " = '" . escape($value) . "'";
                        }
                    break;
                    case 'file':
                        $where[] = "T_$table." . $field_name . ' > 0';
                    break;
                    default:
                        $value = str_replace('*', '%', $value);
                        $where[] = "T_$table." . $field_name . ' ' . $operator . " '" . escape($value) . "'";
                    break;
                }
            }
        }

        // full text search
        if ($conditions['s'] or $conditions['w']) {
            if ($conditions['w']) {
                $conditions['s'] = $conditions['w'];
            }
            
            $words = explode(' ', $conditions['s']);

            foreach ($words as $word) {
                $or = [];

                foreach ($vars['fields'][$section] as $name => $type) {
                    if ( !in_array($type, ['text', 'textarea', 'editor', 'email', 'mobile', 'select', 'id'])) {
                        continue;   
                    }
                    
                    $value = str_replace('*', '%', $word);

                    if ('select' == $type) {
                        if (is_string($vars['options'][$name])) {
                            $option = '';
                            foreach ($vars['fields'][$vars['options'][$name]] as $k => $v) {
                                if ('separator' != $v) {
                                    $option = $k;
                                    break;
                                }
                            }

                            $or[] = 'T_' . underscored($name) . '.' . underscored($option) . " LIKE '%" . escape($value) . "%'";
                        }
                    } else {
                        if ($conditions['w']) {
                            $or[] = "T_$table." . underscored($name) . " REGEXP '[[:<:]]" . escape($value) . "[[:>:]]'";
                        } else {
                            $or[] = "T_$table." . underscored($name) . " LIKE '%" . escape($value) . "%'";
                        }
                    }
                }

                // add or array to where array
                if (count($or)) {
                    $or_str = '';
                    foreach ($or as $w) {
                        $or_str .= $w . ' OR ';
                    }
                    $or_str = substr($or_str, 0, -3);

                    $where[] = '(' . $or_str . ')';
                }
            }
        }
        
        // additional custom conditions
        foreach ($conditions as $k => $v) {
            if (is_int($k)) {
                $where[] = $v;
            }
        }

        // create where string
        if (count($where)) {
            foreach ($where as $w) {
                $where_str .= "\t" . $w . ' AND' . "\n";
            }
            $where_str = "WHERE \n" . substr($where_str, 0, -5);
        }

        // create having string
        if (count($having)) {
            foreach ($having as $w) {
                $having_str .= "\t" . $w . ' AND' . "\n";
            }
            $having_str = "HAVING \n" . substr($having_str, 0, -5);
        }

        // create joins
        if (in_array('select', $vars['fields'][$section]) or in_array('combo', $vars['fields'][$section]) or in_array('radio', $vars['fields'][$section])) {
            $selects = array_keys($vars['fields'][$section], 'select');
            $radios = array_keys($vars['fields'][$section], 'radio');
            $combos = array_keys($vars['fields'][$section], 'combo');

            $keys = array_merge($selects, $radios, $combos);

            foreach ($keys as $key) {
                if (!is_array($vars['options'][$key])) {
                    $option_table = underscored($vars['options'][$key]) or trigger_error('missing options array value for: ' . $key, E_ERROR);

                    $join_id = $this->get_id_field($key);

                    $joins .= "
                        LEFT JOIN $option_table T_" . underscored($key) . ' 
                        ON T_' . underscored($key) . ".$join_id = T_$table." . underscored($key) . '
                    ';

                    foreach ($vars['fields'][$vars['options'][$key]] as $k => $v) {
                        if ('separator' != $v) {
                            $option = $k;
                            break;
                        }
                    }

                    $cols .= ', T_' . underscored($key) . '.' . underscored($option) . " AS '" . underscored($key) . "_label'";
                }
            }
        }
        
        return [
            'where_str' => $where_str,
            'having_str' => $having_str,
            'joins' => $joins,
            'num_results' => $num_results,
            'cols' => $cols,
        ];
    }

    // retrieve cms rows
    public function get($sections, $conditions = null, $num_results = null, $order = null, $asc = true, $prefix = null, $return_query = false)
    {
        global $vars, $auth;

        $section = $sections;
        $table = underscored($sections);

        //set a default prefix to prevent pagination clashing
        if (!$prefix) {
            $prefix = $table;
        }

        // default labels to include first field
        if (!count($vars['labels'][$section])) {
            reset($vars['fields'][$section]);
            $vars['labels'][$section][] = key($vars['fields'][$section]);
        }

        // select columns
        $cols = '';
        foreach ($vars['fields'][$section] as $k => $v) {
            if (in_array($v, ['checkboxes', 'separator'])) {
                continue;   
            }
            
            if ('coords' == $v) {
                $cols .= "\tAsText(T_$table." . underscored($k) . ') AS ' . underscored($k) . ',' . "\n";
            } else {
                $cols .= "\t" . "T_$table.`" . underscored($k) . '` AS `' . underscored($k) . '`,' . "\n";
            }
        }

        // select id column
        $field_id = $this->get_id_field($section);
        $cols .= "\tT_$table.$field_id";

        // determine sort order
        if (!$order) {
            $field_date = array_search('date', $vars['fields'][$section]);
            if ($field_date===false) {
               $field_date = array_search('timestamp', $vars['fields'][$section]);
            }
            
            if (in_array('position', $vars['fields'][$section])) {
                $order = 'T_' . $table . '.position';
            } elseif (false !== $field_date) {
                $order = 'T_' . $table . '.' . underscored($field_date);
                $asc = false;
            } else {
                $label = $vars['labels'][$section][0];
                $type = $vars['fields'][$this->section][$label];

                if (in_array($type, ['select','combo', 'radio']) and !is_array($vars['opts'][$label])) {
                    foreach ($vars['fields'][$vars['options'][$label]] as $k => $v) {
                        if ('separator' == $v) {
                            continue;   
                        }
                        
                        if (is_array($v)) {
                            $order = underscored($label);
                        } else {
                            $order = 'T_' . underscored($label) . '.' . underscored($k);
                        }
                        break;
                    }
                } elseif (is_array($type)) {
                    $order = underscored($vars['labels'][$this->section][0]);
                } elseif ($vars['labels'][$section][0]) {
                    $order = "T_$table." . underscored($vars['labels'][$section][0]);
                } else {
                    $order = "T_$table.id";
                }
            }
        }

        $sql = $this->conditions_to_sql($sections, $conditions, $num_results, $cols);

        $where_str = $sql['where_str'];
        $group_by_str = '';
        $having_str = $sql['having_str'];
        $joins = $sql['joins'];
        $cols = $sql['cols'];
            
        if (true === $num_results) {
            $cols = 'COUNT(*) AS `count`';
        } else {
            $group_by_str = "GROUP BY T_$table.$field_id";
        }

        $query = "SELECT
            $cols
            FROM `$table` T_$table
                $joins
            $where_str
            $group_by_str
            $having_str
            ";
            
        if ($_GET['debug'] and 1 == $auth->user['admin']) {
            debug($query);
        }

        if (true === $return_query) {
            return $query;
        }

        $limit = $sql['num_results'] ?: null;

        $this->p = new paging($query, $limit, $order, $asc, $prefix);

        $content = $this->p->rows;
            
        if (true === $num_results) {
            return $content[0]['count'];
        }

        //spaced versions of field names for compatibility
        foreach ($content as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $content[$k][spaced($k2)] = $v2;
            }
        }

        // nested arrays for checkbox info
        foreach ($vars['fields'][$section] as $field => $type) {
            if ('checkboxes' !== $type) {
                continue;
            }
            
            foreach ($content as $k => $v) {
                if (!is_array($vars['options'][$field]) and $vars['options'][$field]) {
                    $join_id = $this->get_id_field($field);

                    reset($vars['fields'][$vars['options'][$field]]);
                    $key = key($vars['fields'][$vars['options'][$field]]);
                    
                    $rows = sql_query('SELECT `' . underscored($key) . '`,T1.value FROM cms_multiple_select T1
                            INNER JOIN `' . escape(underscored($vars['options'][$field])) . "` T2 
                            ON T1.value = T2.$join_id
                            WHERE
                                T1.section='" . escape($section) . "' AND
                                T1.field='" . escape($field) . "' AND
                                T1.item='" . escape($v['id']) . "'
                            GROUP BY T1.value
                            ORDER BY T2." . underscored($key)
                        );
                } else {
                    $key = 'value';
                    
                    $rows = sql_query("SELECT value FROM cms_multiple_select
                            WHERE
                                section='" . escape($section) . "' AND
                                field='" . escape($field) . "' AND
                                item='" . $v['id'] . "'
                            ORDER BY id
                        ");
                }

                // create cpmma separated label
                $items = [];
                foreach ($rows as $row) {
                    $items[] = $row[$key];
                }

                $items = array_unique($items);
                $label = '';
                foreach ($items as $item) {
                    $label .= $item . ', ';
                }
                $label = substr($label, 0, -2);

                $content[$k][underscored($field)] = $rows;
                $content[$k][underscored($field) . '_label'] = $label;
            }
        }

        // return first array item if one result requested
        return (1 == $sql['num_results']) ? $content[0] : $content; 
    }

    // set cms section to be saved to
    public function set_section($section, $id = null, $editable_fields = null)
    {
        global $vars, $languages, $auth;

        if (!$vars['fields'][$section]) {
            throw new Exception('Section does not exist: ' . $section, E_USER_ERROR);
        }

        $this->section = $section;
        $this->table = underscored($section);

        $this->editable_fields = [];
        if (is_array($editable_fields)) {
            foreach ($editable_fields as $k => $v) {
                $this->editable_fields[$k] = spaced($v);
            }
        } else {
            foreach ($vars['fields'][$this->section] as $k => $v) {
                $this->editable_fields[] = $k;
            }
        }
        
        // don't allow staff to edit admin perms
        if (1 != $auth->user['admin'] and in_array('admin', $this->editable_fields)) {
            unset($this->editable_fields[array_search('admin', $this->editable_fields)]);
        }

        $this->field_id = $this->get_id_field($this->section);

        if (!in_array('id', $vars['fields'][$this->section])) {
            $this->field_id = 'id';
            $row = sql_query('SELECT * FROM `' . $this->table . '` ORDER BY ' . $this->field_id . ' LIMIT 1', 1);
            $id = $row[$this->field_id];
        }

        if ($id) {
            $this->id = $id;
            $this->content = $this->get($section, ['id' => $this->id], 1);

            if (!$this->content) {
                $this->id = null;
            }

            if (in_array('language', $vars['fields'][$this->section])) {
                foreach ($languages as $language) {
                    if ('en' === $language) {
                        continue;
                    }
                    
                    $this->content[$language] = sql_query('SELECT * FROM `' . $this->table . "`
                        WHERE
                            `translated_from`='" . escape($this->id) . "' AND
                            `language`='" . escape($language) . "'
                    ", true);
                }
            }
        } else {
            $this->content = $_GET;
            $this->id = null;
        }
    }

    // set current language
    public function set_language($language)
    {
        $this->language = $language;
    }

    // get field label
    public function get_label()
    {
        global $vars;

        $field = underscored(key($vars['fields'][$this->section]));
        $field_type = $vars['fields'][$this->section][$field];

        if (in_array($field_type, ['select', 'combo'])) {
            if (!is_array($vars['options'][$field])) {
                if (0 == $value) {
                    $value = '';
                } else {
                    $join_id = $this->get_id_field($field);

                    $row = sql_query('SELECT `' . underscored(key($vars['fields'][$vars['options'][$field]])) . '` FROM `' . escape(underscored($vars['options'][$field])) . "` WHERE $join_id='" . escape($value) . "'");
                    $value = '<a href="?option=' . escape($vars['options'][$field]) . '&view=true&id=' . $value . '">' . reset($row[0]) . '</a>';
                }
            } else {
                $value = $vars['options'][$field][$value];
            }
        } else {
            $value = $this->content[$field];
        }

        return truncate($value);
    }
    
    // get name of the id field
    private function get_id_field($section)
    {
        global $vars;
        return in_array('id', $vars['fields'][$section]) ? array_search('id', $vars['fields'][$section]) : 'id';
    }

    // get parent fields child rows
    public function get_children($section, $parent_field, $parent = 0, $depth = 0)
    {
        global $vars;

        reset($vars['fields'][$section]);
        $label = key($vars['fields'][$section]);

        $rows = sql_query("SELECT id,`$label` FROM `" . underscored($section) . '`
            WHERE
                `' . underscored($parent_field) . "` = '$parent'
            ORDER BY `$label`
        ");

        $indent = '';
        for ($i = 0; $i < $depth; $i++) {
            $indent .= '-';
        }

        $parents = [];
        foreach ($rows as $row) {
            if ($row['id'] === $this->id and $section === $this->section) {
                continue;
            }

            $parents[$row['id']] = $indent . ' ' . $row[$label];

            $children = $this->get_children($section, $parent_field, $row['id'], $depth + 1);

            if (count($children)) {
                $parents = $parents + $children;
            }
        }

        return $parents;
    }

    // get field widget
    public function get_field($name, $attribs = '', $placeholder = '', $separator = null, $where = false)
    {
        global $vars, $id, $strs;

        if ($vars['fields'][$this->section][spaced($name)]) {
            $name = spaced($name);
        }

        if ($vars['fields'][$this->section][$name]) {
            $type = $vars['fields'][$this->section][$name];
        } else {
            foreach ($vars['fields'][$this->section] as $k => $v) {
                if ($vars['fields'][$this->section][$k][$name]) {
                    $type = $vars['fields'][$this->section][$k][$name];
                    break;
                }
            }
        }

        $field_name = underscored($name);

        $value = $this->content[$field_name];

        if ('en' != $this->language and '' != $this->language and in_array('language', $vars['fields'][$this->section])) {
            $value = $this->content[$this->language][$field_name];

            $field_name = $this->language . '_' . $field_name;
        }

        $readonly = false;

        if (is_array($this->editable_fields) and !in_array($name, $this->editable_fields)) {
            $readonly = true;
        }

        switch ($type) {
            case 'text':
            case 'float':
            case 'decimal':
            case 'page-name':
            case 'tel':
            case 'hidden':
            case 'email':
            case 'url':
        ?>
            <input type="<?=$type;?>" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if ($readonly) { ?>disabled<?php } ?> <?php if ($placeholder) { ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs;?>>
        <?php
            break;
            case 'int':
        ?>
            <input type="number" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if ($readonly) { ?>disabled<?php } ?> <?php if ($placeholder) { ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs;?>>
        <?php
            break;
            case 'coords':
        ?>
            <input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars(substr($value, 6, -1));?>" <?php if ($readonly) { ?>disabled<?php } ?> size="50" <?=$attribs;?> placeholder="coordinates">
        <?php
            break;
            case 'postcode':
        ?>
            <input type="text" name="<?=$field_name;?>" value="<?=$value;?>" <?php if ($readonly) { ?>disabled<?php } ?> <?php if ($placeholder) { ?>placeholder="<?=$placeholder;?>"<?php } ?> size="10" <?=$attribs;?>>
        <?php
            break;
            case 'mobile':
        ?>
            <input type="text" name="<?=$field_name;?>" value="<?=$value;?>" <?php if ($readonly) { ?>disabled<?php } ?> size="14" <?=$attribs;?>>
        <?php
            break;
            case 'password':
                global $auth;

                if ($auth->hash_password) {
                    $value = '';
                }
        ?>
            <input type="password" name="<?=$field_name;?>" value="<?=$value;?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs;?>>
        <?php
            break;
            case 'textarea':
        ?>
            <textarea name="<?=$field_name;?>" <?php if ($readonly) { ?>disabled<?php } ?> <?php if ($placeholder) { ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs ?: 'class="autogrow"';?>><?=$value;?></textarea>
        <?php
            break;
            case 'editor':
        ?>
            <textarea id="<?=$field_name;?>" name="<?=$field_name;?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs ?: 'rows="25" style="width:100%; height: 400px;"';?> class="tinymce"><?=htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');?></textarea>
        <?php
            break;
            case 'file':
                $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);
        ?>
            <?php if ($value) { ?>
                <input type="hidden" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=$value;?>">
                <?=$file['name'];?>
                <a href="javascript:;" onClick="clearFile('<?=$field_name;?>')">clear</a>
            <?php } else { ?>
                <input type="file" id="<?=$field_name;?>" name="<?=$field_name;?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs;?> />
            <?php } ?>
        <?php
            break;
            case 'phpupload':
        ?>
            <input type="text" name="<?=$field_name;?>" class="upload" value="<?=$value;?>">
        <?php
            break;
            case 'select':
            case 'combo':
            case 'radio':
                if ('radio' == $type) {
                    $vars['options'][$name] = $this->get_options($name, $where);
                    
                    $assoc = is_assoc_array($vars['options'][$name]);
                    foreach ($vars['options'][$name] as $k => $v) {
                        $val = $assoc ? $k : $v; ?>
                    <label <?=$attribs; ?>><input type="radio" name="<?=$field_name; ?>" value="<?=$val; ?>" <?php if ($readonly) { ?>disabled<?php } ?> <?php if (isset($value) and $val == $value) { ?>checked="checked"<?php } ?> <?=$attribs; ?>> <?=$v; ?> &nbsp;</label><?=$separator; ?>
                    <?php
                    }
                } elseif ('combo' == $type) {
                    ?>
                <input type="hidden" name="<?=$field_name; ?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs; ?> value="<?=$value; ?>">
                <input type="text" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs; ?> value="<?=$this->content[$field_name . '_label']; ?>" data-type="combo" data-field="<?=$field_name; ?>">
            <?php
                } else {
                    if (!is_array($vars['options'][$name]) and in_array('parent', $vars['fields'][$vars['options'][$name]])) {
                        ?>
                    <div class="chained" data-name="<?=$field_name; ?>" data-section="<?=$vars['options'][$name]; ?>" data-value="<?=$value; ?>"></div>
                    <?php
                    } else {
                        if (!is_array($vars['options'][$name])) {
                            global $auth;
                            
                            $conditions = [];
                            foreach ($auth->user['filters'][$this->section] as $k => $v) {
                                $conditions[$k] = $v;
                            }
                            
                            $vars['options'][$name] = $this->get_options($name, $where);
                        } ?>
                    <select name="<?=$field_name; ?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs; ?>>
                    <option value=""><?=$placeholder ?: 'Choose'; ?></option>
                        <?=html_options($vars['options'][$name], $value); ?>
                    </select>
                    <?php
                    }
                }
            break;
            case 'checkboxes':
                $value = [];

                if (!is_array($vars['options'][$name]) and $vars['options'][$name]) {
                    if ($this->id) {
                        $join_id = $this->get_id_field($name);

                        $rows = sql_query('SELECT T1.value FROM cms_multiple_select T1
                            INNER JOIN `' . escape(underscored($vars['options'][$name])) . "` T2 ON T1.value=T2.$join_id
                            WHERE
                                section='" . escape($this->section) . "' AND
                                field='" . escape($name) . "' AND
                                item='" . $this->id . "'
                        ");

                        foreach ($rows as $row) {
                            $value[] = $row['value'];
                        }
                    }

                    if (in_array('language', $vars['fields'][$vars['options'][$name]])) {
                        $language = $this->language ? $this->language : 'en';
                        $table = underscored($vars['options'][$name]);

                        foreach ($vars['fields'][$vars['options'][$name]] as $k => $v) {
                            if ('separator' != $v) {
                                $field = $k;
                                break;
                            }
                        }

                        $raw_option = $vars['fields'][$vars['options'][$name]][$field];

                        $cols = '';
                        $cols .= '`' . underscored($field) . '`';

                        $rows = sql_query("SELECT id,$cols FROM
                            $table
                            WHERE
                                language='" . $language . "'
                            ORDER BY `" . underscored($field) . '`
                        ');

                        $options = [];
                        foreach ($rows as $row) {
                            if ($row['translated_from']) {
                                $id = $row['translated_from'];
                            } else {
                                $id = $row['id'];
                            }

                            $options[$id] = $row[underscored($field)];
                        }

                        $vars['options'][$name] = $options;
                    } else {
                        //make sure we get the first field
                        reset($vars['fields'][$vars['options'][$name]]);

                        $vars['options'][$name] = $this->get_options($name, $where);
                    }
                } else {
                    if ($this->id) {
                        $rows = sql_query("SELECT value FROM cms_multiple_select
                            WHERE
                                section='" . escape($this->section) . "' AND
                                field='" . escape($name) . "' AND
                                item='" . $this->id . "'
                        ");

                        foreach ($rows as $row) {
                            $value[] = $row['value'];
                        }
                    }
                }

                ?>
                <?php

                $is_assoc = is_assoc_array($vars['options'][$name]);

                print '<ul class="checkboxes">';
            
                foreach ($vars['options'][$name] as  $k => $v) {
                    $val = $is_assoc ? $k : $v; ?>
                <li><label><input type="checkbox" name="<?=$field_name; ?>[]" value="<?=$val; ?>" <?php if ($readonly) { ?>readonly<?php } ?> <?php if (in_array($val, $value)) { ?>checked="checked"<?php } ?> /> <?=$v; ?></label></li>
                <?php
                }
            
                print '</ul>';

            break;
            case 'parent':
                $parent_field = array_search('parent', $vars['fields'][$this->section]);

                reset($vars['fields'][$this->section]);

                $label = key($vars['fields'][$this->section]);

                $rows = sql_query("SELECT id,`$label` FROM `" . $this->table . "` ORDER BY `$label`");

                $parents = [];
                foreach ($rows as $row) {
                    if ($row['id'] == $this->id) {
                        continue;
                    }
                    $parents[$row['id']] = $row[$label];
                }
        ?>
                <select name="<?=$field_name;?>" <?php if ($readonly) { ?>readonly<?php } ?>>
                <option value=""></option>
                <?=html_options($parents, $value);?>
                </select>
        <?php
            break;
            case 'checkbox':
        ?>
            <input type="checkbox" name="<?=$field_name;?>" value="1" <?php if ($readonly) { ?>disabled<?php } ?> <?php if ($value) { ?>checked<?php } ?>  <?=$attribs;?> />
        <?php
            break;
            case 'files':

                if ($value) {
                    $value = explode("\n", $value);
                }
        ?>

            <ul class="files">
                <?php
                if (is_array($value)) {
                    foreach ($value as $key => $val) {
                        $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'"); ?>
                <li>
                <?php if ($file) { ?>
                    <input type="hidden" name="<?=$field_name;?>[]" value="<?=$val;?>" <?php if ($readonly) { ?>readonly<?php } ?>>
                    <a href="/_lib/cms/file.php?f=<?=$val;?>">
                        <img src="/_lib/cms/file.php?f=<?=$val;?>" style="max-width: 100px; max-height: 100px;"><br>
                        <?=$file[0]['name'];?>
                    </a>
                    <a href="javascript:;" class="link" onClick="delItem(this)">Delete</a>
                <?php } ?>
                </li>
                <?php
                    }
                }
                ?>

                <li>
                    <input type="file" name="<?=$field_name;?>[]" multiple="multiple" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs;?> />
                </li>
            </ul>
        <?php
            break;
            case 'phpuploads':
        ?>
            <textarea name="<?=$field_name;?>" class="upload"><?=$value;?></textarea>
        <?php
            break;
            case 'color':
        ?>
            <input type="color"  name="<?=$field_name;?>" value="<?=$value;?>" <?php if ($readonly) { ?>disabled<?php } ?> size="6" <?=$attribs;?> />
        <?php
            break;
            case 'date':
        ?>
            <input type="text" data-type="date" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && '0000-00-00' != $value) ? $value : '';?>" <?php if ($readonly) { ?>disabled<?php } ?> size="10" <?=$attribs ?: 'style="width:75px;"';?> autocomplete="off" />
        <?php
            break;
            case 'month':
        ?>
            <input type="text" class="month" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && '0000-00-00' != $value) ? $value : '';?>" <?php if ($readonly) { ?>disabled<?php } ?> size="10" <?=$attribs ?: 'style="width:75px;"';?> />
        <?php
            break;
            case 'dob':
        ?>
            <input type="text" data-type="dob" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && '0000-00-00' != $value) ? $value : '';?>" <?php if ($readonly) { ?>disabled<?php } ?> size="10" <?=$attribs ?: 'style="width:75px;"';?> />
        <?php
            break;
            case 'time':
        ?>
            <input type="time" step="1" data-type="time" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=('00:00:00' != $value) ? substr($value, 0, -3) : '';?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs;?> />
        <?php
            break;
            case 'datetime':

            if ($value) {
                $date = explode(' ', $value);
            }
        ?>
            <input type="date" name="<?=$field_name;?>" value="<?=($date[0] and '0000-00-00' != $date[0]) ? $date[0] : '';?>" <?php if ($readonly) { ?>disabled<?php } ?> size="10" <?=$attribs ?: '';?> />
            <input type="time" step="1" name="time[<?=$field_name;?>]" value="<?=$date[1];?>" <?php if ($readonly) { ?>disabled<?php } ?> <?=$attribs;?>>
        <?php
            break;
            case 'rating':
            case 'avg-rating':
        ?>
            <select name="<?=$field_name;?>" class="rating" data-section="<?=$this->section;?>" data-item="<?=$this->content['id'];?>" <?php if ('avg-rating' == $type) {?>data-avg='data-avg'<?php } ?> <?=$attribs;?>>
                <option value="">Choose</option>
                <?=html_options($this->opts['rating'], $value, true);?>
            </select>
        <?php
            break;
        }
    }

    // get formatted value
    public function get_value($name, $return = true)
    {
        global $vars;

        $type = $vars['fields'][$this->section][$name];

        $field_name = underscored($name);

        $value = $this->content[$field_name];

        $id = $this->id;

        if (in_array($type, ['checkboxes'])) {
            $array = [];

            if (!is_array($vars['options'][$name]) and $vars['options'][$name]) {
                $join_id = $this->get_id_field($name);

                //make sure we get the label from the first array item
                reset($vars['fields'][$vars['options'][$name]]);

                $rows = sql_query('SELECT `' . underscored(key($vars['fields'][$vars['options'][$name]])) . '`,T1.value FROM cms_multiple_select T1
                    INNER JOIN `' . escape(underscored($vars['options'][$name])) . "` T2 ON T1.value = T2.$join_id
                    WHERE
                        T1.field='" . escape($name) . "' AND
                        T1.item='" . $id . "' AND
                        T1.section='" . $this->section . "'
                    GROUP BY T1.value
                    ORDER BY T2." . underscored(key($vars['fields'][$vars['options'][$name]])) . '
                ');

                foreach ($rows as $row) {
                    $array[] = '<a href="?option=' . escape($vars['options'][$name]) . '&view=true&id=' . $row['value'] . '">' . current($row) . '</a>';
                }
            } else {
                $rows = sql_query("SELECT value FROM cms_multiple_select
                    WHERE
                        field='" . escape($name) . "' AND
                        item='" . $id . "'
                    ORDER BY id
                ");

                foreach ($rows as $row) {
                    if (is_assoc_array($vars['options'][$name])) {
                        $array[] = $vars['options'][$name][$row['value']];
                    } else {
                        $array[] = current($row);
                    }
                }
            }

            $value = implode('<br>' . "\n", $array);
        }

        if ('url' == $type) {
            $value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        } elseif ('email' == $type) {
            $value = '<a href="mailto:' . $value . '" target="_blank">' . $value . '</a>';
        } elseif ('file' == $type) {
            if ($value) {
                $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'");

                $image_types = ['jpg','jpeg','gif','png'];
                if (in_array(file_ext($file[0]['name']), $image_types)) {
                    $value = '<img src="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file[0]['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
                }
                $value .= '<a href="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file[0]['id'] . '">' . $file[0]['name'] . '</a> <span style="font-size:9px;">' . file_size($file[0]['size']) . '</span>';

                $doc_types = ['pdf','doc','docx','xls','tiff'];
                if (in_array(file_ext($file[0]['name']), $doc_types)) {
                    $value .= '<a href="http://docs.google.com/viewer?url=' . rawurlencode('http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file[0]['id'] . '&auth_user=' . $_SESSION[$auth->cookie_prefix . '_email'] . '&auth_pw=' . md5($auth->secret_phrase . $_SESSION[$auth->cookie_prefix . '_password'])) . '" target="_blank">(view)</a>';
                }
            }
        } elseif ('phpupload' == $type) { ?>
            <img src="/_lib/modules/phpupload/?func=preview&file=<?=$value;?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
            <label id="<?=$name;?>_label"><?=$value;?></label>
        <?php
        } elseif (in_array($type, ['select', 'combo', 'radio'])) {
            if (!is_array($vars['options'][$name])) {
                if ('0' == $value) {
                    $value = '';
                } else {
                    $value = '<a href="?option=' . escape($vars['options'][$name]) . '&view=true&id=' . $value . '">' . $this->content[underscored($name) . '_label'] . '</a>';
                }
            } else {
                if (is_assoc_array($vars['options'][$name])) {
                    $value = $vars['options'][$name][$value];
                }
            } ?>
        <?php
        } elseif ('parent' == $type) {
            reset($vars['fields'][$this->section]);

            $field = key($vars['fields'][$this->section]);

            $row = sql_query("SELECT id,`$field` FROM `" . $this->table . "` WHERE id='" . escape($value) . "' ORDER BY `$field`");

            $value = '<a href="?option=' . escape($this->section) . '&view=true&id=' . $value . '">' . ($row[0][$field]) . '</a>'; ?>
        <?php
        } elseif ('checkbox' == $type) {
            $value = $value ? 'Yes' : 'No';
        } elseif ('files' == $type) {
            if ($value) {
                $value = explode("\n", $value);
            } ?>
                <ul id="<?=$name; ?>_files" class="files">
                <?php
                $count = 0;

            if (is_array($value)) {
                $array = $value;
                foreach ($array as $key => $val) {
                    $count++;

                    if ($val) {
                        $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'");
                    }
                        
                    if (in_array(file_ext($file[0]['name']), $image_types)) {
                        $value = '<img src="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file[0]['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
                    }
                    $value .= '<a href="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file[0]['id'] . '">' . $file[0]['name'] . '</a> <span style="font-size:9px;">' . file_size($file[0]['size']) . '</span><br><br>';
                }
            } ?>
                </ul>
        <?php
        } elseif ('phpuploads' == $type) {
            if ($value) {
                $value = explode("\n", $value);
            } ?>
                <ul id="<?=$name; ?>_files">
                <?php
                $count = 0;

            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $count++; ?>
                <li id="item_<?=$name; ?>_<?=$count; ?>">
                    <img src="/_lib/modules/phpupload/?func=preview&file=<?=$val; ?>" id="file_<?=$name; ?>_<?=$count; ?>_thumb" /><br />
                    <label id="file_<?=$name; ?>_<?=$count; ?>_label"><?=$val; ?></label>
                </li>
                <?php
                }
            } ?>
                </ul>
        <?php
        } elseif ('date' == $type) {
            if ('0000-00-00' != $value and '' != $value) {
                $value = dateformat('d/m/Y', $value);
            }
        } elseif ('dob' == $type) {
            if ('0000-00-00' != $value and '' != $value) {
                $age = age($value);
                $value = dateformat('d/m/Y', $value);
            }

            $value = $value . ' (<?=$age;?>)';
        } elseif ('month' == $type) {
            if ('0000-00-00' != $value and '' != $value) {
                $value = dateformat('F Y', $value);
            }
        } elseif ('datetime' == $type) {
            if ('0000-00-00 00:00:00' != $value) {
                $date = explode(' ', $value);
                $value = dateformat('d/m/Y', $date[0]) . ' ' . $date[1];
            }
        } elseif ('timestamp' == $type) {
            if ('0000-00-00 00:00:00' != $value) {
                $date = explode(' ', $value);
                $value = dateformat('d/m/Y', $date[0]) . ' ' . $date[1];
            }
        } elseif ('number' == $type) {
            $value = number_format($value, 2);
        } elseif ('rating' == $type) {
            $value = '<select name="' . $field_name . '" class="rating" disabled="disabled">
                <option value="">Choose</option>
                ' . html_options($this->opts['rating'], $value, true) . '
            </select>';
        } elseif ('coords' == $type) {
            $value = '<input type="text" class="map" name="' . $field_name . '" value="' . htmlspecialchars(substr($value, 6, -1)) . '" size="50" ' . $attribs . '>';
        }

        if ($return) {
            return $value;
        }
        print $value;
    }

    // get select options
    public function get_options($name, $where = false)
    {
        global $vars, $strs;

        if (!isset($vars['options'][$name])) {
            return false;
        }

        if (!is_array($vars['options'][$name])) {
            $table = underscored($vars['options'][$name]);

            foreach ($vars['fields'][$vars['options'][$name]] as $k => $v) {
                if ('separator' != $v) {
                    $field = $k;
                    break;
                }
            }

            $raw_option = $vars['fields'][$vars['options'][$name]][$field];

            $cols = '`' . underscored($field) . '`';

            //sortable
            $order = in_array('position', $vars['fields'][$vars['options'][$name]]) ? 'position' : $field;

            if (in_array('language', $vars['fields'][$vars['options'][$name]])) {
                $where_str = '';
                if ($where) {
                    $where_str = 'AND ' . $where;
                }

                $language = $this->language ?: 'en';

                $rows = sql_query("SELECT id, $cols FROM
                    $table
                    WHERE
                        language='" . $language . "'
                        $where_str
                    ORDER BY `" . underscored($order) . '`
                ');

                $options = [];
                foreach ($rows as $row) {
                    if ($row['translated_from']) {
                        $id = $row['translated_from'];
                    } else {
                        $id = $row['id'];
                    }

                    $options[$id] = $row[underscored($field)];
                }
            } else {
                $parent_field = array_search('parent', $vars['fields'][$vars['options'][$name]]);

                if (false !== $parent_field) {
                    $options = $this->get_children($vars['options'][$name], $parent_field);
                } else {
                    $where_str = '';
                    if ($where) {
                        $where_str = 'WHERE ' . $where;
                    }

                    $rows = sql_query("SELECT id, $cols FROM
                        $table
                        $where_str
                        ORDER BY `" . underscored($order) . '`
                    ');

                    $options = [];
                    foreach ($rows as $row) {
                        $options[$row['id']] = $row[underscored($field)];
                    }
                }
            }

            $vars['options'][$name] = $options;
        } elseif (is_object($strs)) {
            if (is_assoc_array($vars['options'][$name])) {
                foreach ($vars['options'][$name] as $k => $v) {
                    if ($strs->$v) {
                        $vars['options'][$name][$k] = $strs->$v;
                    }
                }
            } else {
                $new_options = [];
                foreach ($vars['options'][$name] as $k => $v) {
                    if ($strs->$v) {
                        $new_options[$v] = $strs->$v;
                    } else {
                        $new_options[$v] = $v;
                    }
                }

                $vars['options'][$name] = $new_options;
            }
        }

        return $vars['options'][$name];
    }

    // loads the current view
    public function admin()
    {
        global $auth, $vars;
        
        $option = $_GET['option'];
        $section = $option ?: 'index';
        $pos = strpos($section, '/');
        if ($pos) {
            $section = substr($section, 0, $pos);
        }
        
        // redirect if logged out
        if ('login' != $option and !$auth->user['admin']) {
            // check table exists
            if (!table_exists($auth->table)) {
                $cms->check_table($auth->table, $vars['fields'][$this->table]);
                sql_query('ALTER TABLE `' . $auth->table . '` ADD UNIQUE `email` ( `email` )');
            }
    
            // check admin user exists
            $row = sql_query('SELECT * FROM ' . $auth->table . ' LIMIT 1', 1);
            if (!$row) {
                $default_pass = '123';
                if ($auth->hash_password) {
                    $default_pass = $auth->create_hash($default_pass);
                }
                
                sql_query('INSERT INTO ' . $auth->table . " SET email='admin', password='" . $default_pass . "', admin='1'");
            }
    
            redirect('/admin?option=login');
        }

        // check permissions
        if ($auth->user['admin'] > 1 and table_exists('cms_privileges')) {
            $rows = sql_query("SELECT * FROM cms_privileges
                WHERE
                    user='" . escape($auth->user['id']) . "'
            ");

            foreach ($rows as $row) {
                $auth->user['privileges'][$row['section']] = $row['access'];
                $pairs = explode('&', $row['filter']);

                foreach ($pairs as $pair) {
                    $arr = explode('=', $pair);
                    $auth->user['filters'][$row['section']][underscored($arr[0])] = urldecode($arr[1]);
                }
            }
        }

        if (file_exists('_tpl/admin/' . underscored($option) . '.php')) {
            $this->template(underscored($option) . '.php');
        } elseif (in_array($option, ['configure', 'choose_filter', 'shop_order', 'shop_orders'])) {
            $this->template($option . '.php', true);
        } elseif ('login' == $option) {
            $this->login();
        } elseif ('index' != $option) {
            $this->default_section($option);
        } else {
            $this->main();
        }
    }

    // send admin notification
    public function notify($subject = null, $to = null)
    {
        global $vars, $from_email;

        if (!$subject or is_numeric($subject)) {
            $subject = 'New submission to: ' . $this->section;
        }

        $msg = 'New submission to ' . $this->section . ":\n\n";

        $this->set_section($this->section, $this->id);

        foreach ($vars['fields'][$this->section] as $name => $type) {
            $msg .= ucfirst(spaced($name)) . ': ' . $this->get_value($name) . "\n";
        }
        $msg .= "\n" . 'https://' . $_SERVER['HTTP_HOST'] . '/admin?option=' . rawurlencode($this->section) . '&edit=true&id=' . $this->id;

        $msg = nl2br($msg);

        send_mail([
            'subject' => $subject,
            'content' => $msg,
            'to_email' => $to,
        ]);
    }

    // handle ajax form submission
    public function submit($notify = null, $other_errors = [])
    {
        global $vars;

        $errors = $this->validate();

        if (is_array($other_errors)) {
            $errors = array_values(array_unique(array_merge($errors, $other_errors)));
        }

        //handle validation
        if (count($errors)) {
            //validateion failed
            die(json_encode($errors));
        } elseif ($_POST['validate']) {
            //validation passed
            die('1');
        }
        $this->id = $this->save();

        if ($notify) {
            $this->notify(null, $notify);
        }

        return $this->id;
    }

    // validate fields before saving
    public function validate($data = null)
    {
        global $vars, $languages, $strs;

        if (!is_array($data)) {
            $data = $_POST;
        }

        $errors = $this->trigger_event('beforeValidate', ['data' => $data]);

        if (!is_array($errors)) {
            $errors = [];
        }

        // validate unique keys
        $table_keys = sql_query('SHOW keys FROM `' . $this->table . '`');

        $keys = [];
        foreach ($table_keys as $v) {
            if (0 == $v['Non_unique']) {
                $keys[$v['Key_name']][] = $v['Column_name'];
            }
        }

        if (count($languages) and in_array('language', $vars['fields'][$this->section])) {
            $languages = array_merge(['en'], $languages);
        } else {
            $languages = ['en'];
        }

        foreach ($languages as $language) {
            foreach ($vars['fields'][$this->section] as $k => $v) {
                if ($this->editable_fields and !in_array($k, $this->editable_fields)) {
                    continue;
                }

                $field_name = underscored($k);

                if ('en' == $language) {
                    $name = $field_name;
                } else {
                    $name = $language . '_' . $field_name;
                }

                //check items are valid
                $is_valid = true;
                if ($data[$name]) {
                    switch ($v) {
                        case 'date':
                            if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $data[$name])) {
                                $is_valid = false;
                            }
                        break;
                        case 'url':
                            if ('http://' == $data[$name]) {
                                $data[$name] = '';
                            }
    
                            if ($data[$name] and !is_url($data[$name])) {
                                $is_valid = false;
                            }
                        break;
                        case 'email':
                            if (!is_email($data[$name])) {
                                $is_valid = false;
                            }
                        break;
                        case 'tel':
                            if (!is_tel($data[$name])) {
                                $is_valid = false;
                            }
                        break;
                        case 'int':
                        case 'decimal':
                            if (!is_numeric($data[$name])) {
                                $is_valid = false;
                            }
                        break;
                        case 'postcode':
                            if (
                                $data[$name] and
                                !format_postcode($data[$name]) and
                                (!$data['country'] or 'UK' == $data['country'])
                            ) {
                                $is_valid = false;
                            } elseif (format_postcode($data[$name])) {
                                $data[$name] = format_postcode($data[$name]);
                            }
                        break;
                        case 'mobile':
                            if (!format_mobile($data[$name])) {
                                $is_valid = false;
                            } elseif (format_mobile($data[$name])) {
                                $data[$name] = format_mobile($data[$name]);
                            }
                        break;
                    }
                }
                
                if ($is_valid === false) {
                    $errors[] = $name;
                }

                // check required fields
                if (
                    in_array($k, $vars['required'][$this->section]) and 
                    ('' === $data[$name] or !isset($data[$name]))
                ) {
                    if ($_FILES[$name]) {
                        continue;
                    }

                    if ('password' == $v and $this->id) {
                        continue;
                    }

                    $errors[] = $name;
                    continue;
                }

                //check keys
                $in_use = is_object($strs) ? $strs->inUse : 'in use';
                foreach ($keys as $key => $fields) {
                    if (!in_array($name, $fields)) {
                        continue;   
                    }
                    
                    $where = [];
                    foreach ($fields as $field) {
                        $where[] = '`' . escape($field) . "`='" . escape($data[$field]) . "'";
                    }
                    $where[] = '`' . $this->field_id . "`!='" . escape($this->id) . "'";

                    $where_str = '';
                    foreach ($where as $w) {
                        $where_str .= "\t" . $w . ' AND' . "\n";
                    }
                    $where_str = "WHERE \n" . substr($where_str, 0, -5);

                    $field = sql_query('SELECT * FROM `' . $this->table . "`
                        $where_str
                        LIMIT 1
                    ", 1);

                    if ($field) {
                        $errors[] = $key . ' ' . $in_use;

                        foreach ($fields as $field) {
                            $errors[] = $field . ' ' . $in_use;
                        }
                        break;
                    }
                }
            }
        }

        if (count($errors)) {
            $errors = array_values(array_unique($errors));
        }

        return $errors;
    }

    // build update query from array
    public function build_query($field_arr, $data)
    {
        global $vars, $languages, $auth;

        $column_data = sql_query('SHOW COLUMNS FROM `' . $this->table . '`');

        $null_fields = [];
        foreach ($column_data as $v) {
            if ('YES' == $v['Null']) {
                $null_fields[] = $v['Field'];
            }
        }

        foreach ($field_arr as $k => $v) {
            if (
                   in_array($v, ['id', 'related', 'timestamp', 'separator', 'translated-from', 'polygon']) or
                $this->editable_fields and !in_array($k, $this->editable_fields)
            ) {
                continue;
            }

            if (is_array($v)) {
                $this->build_query($v, $data);
            } else {
                $k = underscored($k);
                
                if ('language' == $v) {
                    $this->query .= "`$k`='" . escape($this->language) . "',\n";

                    if ('en' !== $this->language) {
                        $this->query .= "`translated_from`='" . escape($this->id) . "',\n";
                    }
                    continue;
                }

                if ('en' == $this->language) {
                    $name = $k;
                } else {
                    $name = $this->language . '_' . $k;
                }

                if ('position' == $v and !$this->id and !isset($data[$name])) {
                    //find last position
                    $max_pos = sql_query('SELECT MAX(position) AS `max_pos` FROM `' . $this->table . '`', 1);
                    $max_pos = $max_pos['max_pos'] + 1;

                    $this->query .= "`$k`='" . escape($max_pos) . "',\n";
                    continue;
                } elseif ('position' == $v and !isset($data[$name])) {
                    continue;
                }

                if ('password' == $v) {
                    //leave passwords blank to keep
                    if ('' == $data[$name]) {
                        continue;
                    }
                    if ($auth->hash_password) {
                        $data[$name] = $auth->create_hash($data[$name]);
                    }
                }

                // only admin can set admin permission
                if (1 !== $auth->user['admin'] and 'admin' == $k) {
                    continue;
                }

                if ('url' == $v and 'http://' == $data[$name]) {
                    $data[$name] = '';
                } elseif ('datetime' == $v) {
                    if ($data['time']) {
                        $data[$name] .= ' ' . $data['time'][$name] . ':00';
                    }
                } elseif ('date' == $v or 'dob' == $v) {
                } elseif ('month' == $v) {
                    $data[$name] .= '-01';
                } elseif ('file' == $v) {
                    if ('UPLOAD_ERR_OK' == $_FILES[$name]['error']) {
                        $size = filesize($_FILES[$name]['tmp_name']);

                        $result = sql_query("INSERT INTO files SET
                            date=NOW(),
                            name='" . escape($_FILES[$name]['name']) . "',
                            size='" . escape($size) . "',
                            type='" . escape($_FILES[$name]['type']) . "'
                        ");

                        $data[$name] = sql_insert_id();

                        //check folder exists
                        if (!file_exists($vars['files']['dir'])) {
                            mkdir($vars['files']['dir']);
                        }

                        if ($vars['files']['dir']) {
                            rename($_FILES[$name]['tmp_name'], $vars['files']['dir'] . $data[$name]);
                        } else {
                            sql_query("UPDATE files SET
                                data='" . escape(file_get_contents($_FILES[$name]['tmp_name'])) . "'
                                WHERE
                                    id='" . escape($data[$name]) . "'
                            ");
                        }

                        //thumb
                        if ($_POST[$name . '_thumb']) {
                            // Grab the MIME type and the data with a regex for convenience
                            if (preg_match('/data:([^;]*);base64,(.*)/', $_POST[$name . '_thumb'], $matches)) {
                                // Decode the data
                                $thumb = $matches[2];
                                $thumb = str_replace(' ', '+', $thumb);
                                $thumb = base64_decode($thumb);

                                $file_name = $vars['files']['dir'] . $data[$name] . '_thumb';
                                file_put_contents($file_name, $thumb);
                            } else {
                                die('no mime type');
                            }
                        }
                    } elseif (!$data[$name] and $row[$name]) {
                        sql_query("DELETE FROM files
                            WHERE
                            id='" . escape($row[$name]) . "'
                        ");

                        if ($vars['files']['dir']) {
                            unlink($vars['files']['dir'] . $row[$name]);
                        }

                        $data[$name] = 0;
                    }
                } elseif ('files' == $v) {
                    $files = $data[$name];

                    if (is_array($_FILES[$name])) {
                        foreach ($_FILES[$name]['error'] as $key => $error) {
                            if ('UPLOAD_ERR_OK' !== $error) {
                                continue;   
                            }
                            
                            $content = file_get_contents($_FILES[$name]['tmp_name'][$key]);

                            sql_query("INSERT INTO files SET
                                date=NOW(),
                                name='" . escape($_FILES[$name]['name'][$key]) . "',
                                size='" . escape(strlen($content)) . "',
                                type='" . escape($_FILES[$name]['type'][$key]) . "'
                            ");

                            $files[] = sql_insert_id();

                            //check folder exists
                            if (!file_exists($vars['files']['dir'])) {
                                mkdir($vars['files']['dir']);
                            }

                            file_put_contents($vars['files']['dir'] . sql_insert_id(), $content) or trigger_error("Can't save " . $vars['files']['dir'] . $data[$name], E_ERROR);
                        }
                    }

                    if ($this->id) {
                        //clean up
                        $row = sql_query("SELECT `$name` FROM `" . $this->table . '`', 1);

                        $old_files = explode("\n", $row[$name]);

                        foreach ($old_files as $old_file) {
                            if (in_array($old_file, $files)) {
                                sql_query("DELETE FROM files
                                    WHERE
                                    id='" . escape($old_file['id']) . "'
                                ");

                                if ($vars['files']['dir']) {
                                    unlink($vars['files']['dir'] . $old_file['id']);
                                }
                            }
                        }
                    }

                    if (is_array($files)) {
                        $data[$name] = implode("\n", $files);
                    }
                    $data[$name] = trim($data[$name]);
                } elseif ('checkboxes' == $v) {
                    continue;
                } elseif ('postcode' == $v) {
                    $data[$name] = format_postcode($data[$name]);
                } elseif ('mobile' == $v) {
                    if ($data[$name]) {
                        $data[$name] = format_mobile($data[$name]);
                    }
                } elseif ('ip' == $v) {
                    if (!$this->id) {
                        $data[$name] = $_SERVER['REMOTE_ADDR'];
                    } elseif (!$data[$name]) {
                        continue;
                    }
                } elseif ('page-name' == $v) {
                    $data[$name] = str_to_pagename($data[$name], false);
                } elseif ('coords' == $v) {
                    $this->query .= "`$k`=GeomFromText('POINT(" . escape($data[$name]) . ")'),\n";
                    continue;
                } elseif ('text' == $v or 'textarea' == $v) {
                    $data[$name] = strip_tags($data[$name]);
                } elseif ('editor' == $v) {
                    $doc = new DOMDocument();
                    $doc->loadHTML('<div>' . $data[$name] . '</div>');
                    
                    $container = $doc->getElementsByTagName('div')->item(0);
                    $container = $container->parentNode->removeChild($container);
                    while ($doc->firstChild) {
                        $doc->removeChild($doc->firstChild);
                    }
                    
                    while ($container->firstChild) {
                        $doc->appendChild($container->firstChild);
                    }
                    
                    // remove script tags
                    $script = $doc->getElementsByTagName('script');
                    foreach ($script as $item) {
                        $item->parentNode->removeChild($item);
                    }
                    
                    $data[$name] = $doc->saveHTML();
                }

                if (is_array($data[$name])) {
                    $data[$name] = implode("\n", strip_tags($data[$name]));
                }

                if ((!isset($data[$name]) or '' === $data[$name]) and in_array($k, $null_fields)) {
                    $this->query .= "`$k`=NULL,\n";
                } else {
                    $this->query .= "`$k`='" . escape($data[$name]) . "',\n";
                }
            }
        }
    }

    // save data, called after set_section
    public function save($data = null)
    {
        global $vars, $languages, $auth;

        // default to post data
        if ($data === null) {
            $data = $_POST;
        }

        // fire event
        $result = $this->trigger_event('beforeSave', ['data' => $data]);
        if (is_array($result)) {
            $data = $result;
        }
        
        // force save data to match privileges
        foreach ($auth->user['filters'][$this->section] as $k => $v) {
            $data[$k] = $v;
        }

        //remember language
        if (!count($languages)) {
            $languages = ['en'];
        }
        
        $current_language = $this->language;

        foreach ($languages as $language) {
            $this->language = $language;

            //build query
            $this->query = '';
            $this->build_query($vars['fields'][$this->section], $data);
            $this->query = substr($this->query, 0, -2);

            if ($this->id) {
                if ('en' === $language) {
                    $language_exists = true;
                    $where_str = $this->field_id . "='" . escape($this->id) . "'";
                } else {
                    $row = sql_query('SELECT * FROM `' . $this->table . "`
                        WHERE
                            `translated_from`='" . escape($this->id) . "' AND
                            language='" . escape($language) . "'
                    ", 1);
    
                    if ($row) {
                        $language_exists = true;
                        $where_str = "`translated_from`='" . escape($this->id) . "' AND language='" . escape($language) . "'";
                    } else {
                        $language_exists = false;
                    }
                }
            }
            
            // remember old state
            if ($this->id) {
                if ('en' === $language) {
                    $row = sql_query('SELECT * FROM `' . $this->table . "`
                        WHERE
                            `id`='" . escape($this->id) . "'
                    ", 1);
                } else {
                    $row = sql_query('SELECT * FROM `' . $this->table . "`
                        WHERE
                            `translated_from`='" . escape($this->id) . "' AND
                            language='" . escape($language) . "'
                    ", 1);
                }
            }

            // save data
            if ($this->id and $language_exists) {
                sql_query('UPDATE `' . $this->table . '` SET
                    ' . $this->query . "
                    WHERE $where_str
                ");
            } else {
                sql_query('INSERT IGNORE INTO `' . $this->table . '` SET
                    ' . $this->query . '
                ');

                if ('en' == $language) {
                    $this->id = sql_insert_id();
                }
            }
            
            //log it
            if (table_exists('cms_logs')) {
                $details = '';
                
                $task = 'add';
                if ($this->id) {
                    $task = 'edit';
                
                    if ('en' === $language) {
                        $updated_row = sql_query('SELECT * FROM `' . $this->table . "`
                            WHERE
                                `id`='" . escape($this->id) . "'
                        ", 1);
                    } else {
                        $updated_row = sql_query('SELECT * FROM `' . $this->table . "`
                            WHERE
                                `translated_from`='" . escape($this->id) . "' AND
                                language='" . escape($language) . "'
                        ", 1);
                    }
                    
                    // find changes
                    foreach ($updated_row as $k => $v) {
                        if ($row[$k] != $v) {
                            $details .= $k . '=' . $v . "\n";
                        }
                    }
                }
                
                $this->save_log($this->section, $this->id, $task, $details);
            }

            foreach ($vars['fields'][$this->section] as $k => $v) {
                if ('checkboxes' !== $v or ($this->editable_fields and !in_array($k, $this->editable_fields))) {
                    continue;
                }

                $name = ('en' == $language) ? $k : $language . '_' . $k;
                $name = underscored($name);

                sql_query("DELETE FROM cms_multiple_select
                    WHERE
                        section='" . escape($this->section) . "' AND
                        field='" . escape($k) . "' AND
                        item='" . escape($this->id) . "'
                ");

                foreach ($data[$name] as $v) {
                    sql_query("INSERT INTO cms_multiple_select SET
                        section='" . escape($this->section) . "',
                        field='" . escape($k) . "',
                        item='" . escape($this->id) . "',
                        value='" . escape($v) . "'
                    ");
                }

                continue;
            }
        }

        //restore language
        $this->language = $current_language;
        
        $this->trigger_event('save', [$this->id, $data]);
        $this->saved = true;

        return $this->id;
    }
    
    public function save_log($section, $id, $task, $details)
    {
        global $auth;
                
        sql_query("INSERT INTO cms_logs SET
            user = '" . $auth->user['id'] . "',
            section = '" . escape($section) . "',
            item = '" . escape($id) . "',
            task = '" . $task . "',
            details = '" . escape($details) . "'
        ");
    }

    public function trigger_event($event, $args)
    {
        global $cms_handlers;

        if (is_array($cms_handlers)) {
            foreach ($cms_handlers as $handler) {
                if (!is_array($handler['section'])) {
                    $handler['section'] = [$handler['section']];
                }
                
                if (
                    in_array($this->section, $handler['section']) and
                    $handler['event'] === $event
                ) {
                    return call_user_func_array($handler['handler'], (array) $args);
                }
            }
        }
    }

    public function template($include, $local = false)
    {
        global $vars, $auth, $shop_enabled, $languages, $live_site, $cms_buttons, $message;

        ob_start();
        if ($local) {
            require(dirname(__FILE__) . '/_tpl/' . $include);
        } else {
            require('_tpl/admin/' . $include);
        }
        $include_content = ob_get_contents();
        ob_end_clean();

        if (!$title and preg_match('/<h1>([\s\S]*?)<\/h1>/i', $include_content, $matches)) {
            $title = strip_tags($matches[1]);
        }
        
        $this->filters = sql_query("SELECT * FROM cms_filters WHERE user = '" . escape($auth->user['id']) . "'");

        require(dirname(__FILE__) . '/_tpl/template.php');
        exit;
    }

    public function login()
    {
        $this->template('login.php', true);
    }

    public function main()
    {
        $this->template('index.php', true);
    }

    public function default_section($option)
    {
        global $vars, $sid;

        $this->section = $option;

        $this->table = underscored($option);

        if ($vars['fields'][$this->section]) {
            $this->check_table($this->table, $vars['fields'][$this->section]);
        } else {
            $index = true;
        }

        if ($index) {
            $this->template('default_index.php', true);
        } elseif ($_GET['edit']) {
            $this->template('default_edit.php', true);
        } elseif ($_GET['view'] or !in_array('id', $vars['fields'][$this->section])) {
            $this->template('default_view.php', true);
        } else {
            $this->template('default_list.php', true);
        }
    }
}
