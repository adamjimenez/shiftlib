<?php

class cms
{
    const VERSION = 'v2.0.1';
    
    // hide these field types from the list view
    public $hidden_columns = ['id', 'password', 'editor', 'textarea', 'checkboxes'];

    public function __construct()
    {
        global $cms_buttons, $vars;

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
    }

    /**
     * @param string $table
     * @param array $fields OR NULL
     * @throws Exception
     */
    public function check_table(string $table, $fields = [])
    {
        $select = sql_query("SHOW TABLES LIKE '$table'");
        if (!$select) {
            //build table query
            $query = '';
            $indexes = [];

            foreach ($fields as $name => $type) {
                if ('indexes' === $name) {
                    $indexes = $type;
                    continue;
                }

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

            foreach ($indexes as $index) {
                sql_query("ALTER TABLE `$table` ADD " . strtoupper($index['type']) . ' `' . $index['name'] . '` ( ' . implode(',', $index['fields']) . ' )');
            }
        }
    }

    /**
     * @param string $type
     * @return string|null
     */
    public function form_to_db(string $type)
    {
        if ($component = $this->get_component($type)) {
            return $component->getFieldSql();
        }

        switch ($type) {
            case 'id':
            case 'separator':
                break;
            case 'read':
            case 'deleted':
                return 'TINYINT';
                break;
            default:
                return "VARCHAR( 140 ) NOT NULL DEFAULT ''";
                break;
        }
    }

    // export items to csv
    public function export_items($section, $conditions, $select_all_pages)
    {
        global $auth, $db_connection;

        set_time_limit(300);
        ob_end_clean();

        if (!$select_all_pages) {
            $ids = is_array($conditions) ? $conditions : [$conditions];
            $conditions = ['id' => $ids];
        }

        // staff perms
        foreach ($auth->user['filters'][$section] as $k => $v) {
            $conditions[$k] = $v;
        }

        $sql = $this->conditionsToSql($section, $conditions);

        $table = underscored($section);
        $field_id = in_array('id', $vars['fields'][$section]) ? array_search('id', $vars['fields'][$section]) : 'id';

        $query = "SELECT *
            FROM `$table` T_$table
            " . $sql['joins'] . '
            ' . $sql['where_str'] . '
            GROUP BY
            T_' . $table . '.' . $field_id . '
            ' . $sql['having_str'];

        $result = mysqli_query($db_connection, $query);

        if (false === $result) {
            debug($query);
            throw new Exception(mysqli_error(), E_ERROR);
        }

        header('Content-Type: text/comma-separated-values; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $section . '.csv"');

        $i = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $data = '';

            // get headings
            if (0 == $i) {
                $j = 0;
                foreach ($row as $k => $v) {
                    $data .= '"' . $k . '",';
                    $headings[$j] = $k;
                }
                $data = substr($data, 0, -1);
                $data .= "\n";
                $j++;
            }

            $j = 0;
            foreach ($row as $k => $v) {
                if (is_array($v)) {
                    $data .= '"' . str_replace('"', '""', serialize($v)) . '",';
                } else {
                    $data .= '"' . str_replace('"', '""', $v) . '",';
                }

                $j++;
            }
            $data = substr($data, 0, -1);
            $data .= "\n";
            $i++;

            print($data);
        }

        exit();
    }

    public function delete_items($section, $conditions, $select_all_pages = false) // used in admin system
    {
        global $auth;

        if (1 == $auth->user['admin'] || 2 == $auth->user['privileges'][$section]) {
            if ($select_all_pages) {
                $rows = $this->get($section, $conditions);

                $items = [];
                foreach ($rows as $v) {
                    $items[] = $v['id'];
                }
            } else {
                $items = is_array($conditions) ? $conditions : [$conditions];
            }

            if (false !== $this->delete($section, $items)) {
                $_SESSION['message'] = 'The items have been deleted';
                return true;
            }
        }

        $_SESSION['message'] = 'Permission denied';
        return false;
    }

    public function delete($section, $ids) // no security checks
    {
        global $vars;

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (false === $this->trigger_event('beforeDelete', [$ids])) {
            return false;
        }

        $field_id = $this->get_id_field($section);

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
            } else {
                if (!in_array('id', $vars['fields'][$section])) {
                    sql_query('TRUNCATE TABLE `' . escape(underscored($section)) . '`');
                } else {
                    sql_query('DELETE FROM `' . escape(underscored($section)) . '`
                        WHERE ' . $field_id . "='$id'
                            LIMIT 1
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
    
    public function conditions_to_sql($section, $conditions = [], $num_results = null, $cols = null)
    {
        return $this->conditionsToSql($section, $conditions, $num_results, $cols);
    }

    // create search params, used by get()
    public function conditionsToSql($section, $conditions = [], $num_results = null, $cols = null)
    {
        global $vars, $auth;

        $table = underscored($section);
        $field_id = $this->get_id_field($section);

        // check for id or page name
        $id = null;
        if (is_numeric($conditions)) {
            $id = $conditions;
            $num_results = 1;
        } elseif (is_string($conditions) && in_array('page_name', $vars['fields'][$section])) {
            $field_page_name = array_search('page_name', $vars['fields'][$section]);
            $conditions = [$field_page_name => $conditions];
            $num_results = 1;
        // deprecated
        } elseif (is_string($conditions) && in_array('page-name', $vars['fields'][$section])) {
            $field_page_name = array_search('page-name', $vars['fields'][$section]);
            $conditions = [$field_page_name => $conditions];
            $num_results = 1;
        } elseif (!in_array('id', $vars['fields'][$section])) {
            $id = 1;
        }

        if (is_numeric($id)) {
            $conditions = ['id' => $id];
            $num_results = 1;
        }

        // restrict results to staff perms
        foreach ($auth->user['filters'][$this->section] as $k => $v) {
            $conditions[$k] = $v;
        }

        // add underscores to conditions (broken page name), should we enforce underscores?
        foreach ($conditions as $k => $v) {
            $conditions[underscored($k)] = $v;
        }

        // filter deleted
        if (in_array('deleted', $vars['fields'][$section]) && !isset($conditions['deleted']) && !$id) {
            $where[] = "T_$table.deleted = 0";
        }

        $joins = '';
        foreach ($vars['fields'][$section] as $name => $type) {
            $field_name = underscored($name);
            $value = $conditions[$field_name];

            if (!isset($value) || '' === $value) {
                continue;
            }

            if ($component = $this->get_component($type)) {
                // deprecated
                if (is_array($conditions) && $conditions['end'][$field_name]) {
                    $conditions['func'][$field_name] = ['end' => $conditions['end'][$field_name]];
                }
                $where[] = $component->conditionsToSql($field_name, $value, $conditions['func'][$field_name], "T_$table.");
            }

            switch ($type) {
                case 'checkboxes':
                    // todo: move to component
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $joins .= ' LEFT JOIN cms_multiple_select T_' . $field_name . ' ON T_' . $field_name . '.item=T_' . $table . '.' . $field_id;

                    $or = [];
                    foreach ($value as $v) {
                        $v = is_array($v) ? $v['value'] : $v;
                        $or[] = 'T_' . $field_name . ".value = '" . escape($v) . "' AND 
                            T_" . $field_name . ".field = '" . escape($name) . "' AND
                            T_" . $field_name . ".section='" . $section . "'";
                    }

                    $or_str = implode(' OR ', $or);
                    $where[] = '(' . $or_str . ')';
                    break;
                case 'postcode':
                    // todo: move to component and replace with ST_Distance_Sphere when supported (https://jira.mariadb.org/browse/MDEV-13467)
                    if (calc_grids($value) && is_numeric($conditions['func'][$field_name])) {
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
                        }
                    }
                    break;
            }
        }

        // full text search
        if ($conditions['s'] || $conditions['w']) {
            if ($conditions['w']) {
                $conditions['s'] = $conditions['w'];
            }

            $words = explode(' ', $conditions['s']);

            foreach ($words as $word) {
                $or = [];

                foreach ($vars['fields'][$section] as $name => $type) {
                    if (!in_array($type, ['text', 'textarea', 'editor', 'email', 'mobile', 'select', 'id'])) {
                        continue;
                    }

                    $value = str_replace('*', '%', $word);

                    if ('select' == $type) {
                        if (is_string($vars['options'][$name])) {
                            $option = $this->get_option_label($name);
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

                // add 'or' array to 'where' array
                if (count($or)) {
                    $or_str = implode(' OR ', $or);
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
        $where_str = '';
        if (count($where)) {
            $where_str = "WHERE \n" . implode(" AND \n", array_filter($where));
        }

        // create having string
        $having_str = '';
        if (count($having)) {
            $having_str = "HAVING \n" . implode(" AND \n", array_filter($having));
        }

        // create joins
        if (
            in_array('select', $vars['fields'][$section]) ||
            in_array('combo', $vars['fields'][$section]) ||
            in_array('radio', $vars['fields'][$section])
        ) {
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

                    $option = $this->get_option_label($key);

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

    /**
     * retrieve cms rows
     *
     * @param $section
     * @param null $conditions
     * @param null $num_results
     * @param null $order
     * @param bool $asc
     * @param null $prefix
     * @param bool $return_query
     * @throws Exception
     * @return array|bool|mixed|string
     */
    public function get($section, $conditions = null, $num_results = null, $order = null, $asc = true, $prefix = null, $return_query = false)
    {
        global $vars, $auth;

        if (!is_array($vars['fields'][$section])) {
            trigger_error('missing section: ' . $section, E_ERROR);
            return false;
        }

        $table = underscored($section);

        //set a default prefix to prevent pagination clashing
        if (!$prefix) {
            $prefix = $table;
        }

        // select columns
        $cols = '';
        foreach ($vars['fields'][$section] as $name => $type) {
            if (in_array($type, ['checkboxes', 'separator'])) {
                continue;
            }

            $col = "T_$table." . underscored($name);
            if ('coords' == $type) {
                $col = 'AsText(' . $col . ')';
            }

            $cols .= "\t" . $col . ' AS `' . underscored($name) . '`,' . "\n";
        }

        // select id column
        $field_id = $this->get_id_field($section);
        $cols .= "\tT_$table.$field_id";

        // determine sort order
        if (!$order) {
            $field_date = array_search('date', $vars['fields'][$section]);
            if (false === $field_date) {
                $field_date = array_search('timestamp', $vars['fields'][$section]);
            }

            if (in_array('position', $vars['fields'][$section])) {
                $order = 'T_' . $table . '.position';
            } elseif (false !== $field_date) {
                $order = 'T_' . $table . '.' . underscored($field_date);
                $asc = false;
            } else {
                $label = $vars['fields'][$section][0];
                $type = $vars['fields'][$this->section][$label];

                // order options by value instead of key
                if (in_array($type, ['select', 'combo', 'radio']) && !is_array($vars['opts'][$label])) {
                    $key = $this->get_option_label($label);
                    $order = 'T_' . underscored($label) . '.' . underscored($key);
                } elseif ($vars['fields'][$section][0]) {
                    $order = "T_$table." . underscored($vars['fields'][$section][0]);
                } else {
                    $order = "T_$table.id";
                }
            }
        }

        $sql = $this->conditionsToSql($section, $conditions, $num_results, $cols);

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

        if (true === $return_query) {
            return $query;
        }

        $limit = $sql['num_results'] ?: null;
        $this->p = new paging($query, $limit, $order, $asc, $prefix);

        $content = $this->p->rows;

        if (true === $num_results) {
            return $content[0]['count'];
        }

        // Deprecated: spaced versions of field names for compatibility
        foreach ($content as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $content[$k][spaced($k2)] = $v2;
            }
        }

        // nested arrays for checkbox info
        foreach ($vars['fields'][$section] as $name => $type) {
            if ('checkboxes' !== $type) {
                continue;
            }

            foreach ($content as $k => $v) {
                if (!is_array($vars['options'][$name]) && $vars['options'][$name]) {
                    $join_id = $this->get_id_field($name);
                    $key = $this->get_option_label($name);

                    $rows = sql_query(
                        'SELECT `' . underscored($key) . '`,T1.value FROM cms_multiple_select T1
                        INNER JOIN `' . escape(underscored($vars['options'][$name])) . "` T2 
                        ON T1.value = T2.$join_id
                        WHERE
                            T1.section='" . escape($section) . "' AND
                            T1.field='" . escape($name) . "' AND
                            T1.item='" . escape($v['id']) . "'
                        GROUP BY T1.value
                        ORDER BY T2." . underscored($key)
                    );
                } else {
                    $key = 'value';

                    $rows = sql_query("SELECT value FROM cms_multiple_select
                        WHERE
                            section='" . escape($section) . "' AND
                            field='" . escape($name) . "' AND
                            item='" . $v['id'] . "'
                        ORDER BY id
                    ");
                }

                // create cpmma separated label
                $items = [];
                foreach ($rows as $row) {
                    $items[] = $row[$key];
                }
                $label = implode(', ', array_unique($items));

                $content[$k][underscored($name)] = $rows;
                $content[$k][underscored($name) . '_label'] = $label;
            }
        }

        // return first array item if one result requested
        return (1 == $sql['num_results']) ? $content[0] : $content;
    }

    /**
     * set cms section to be saved to
     *
     * @param $section
     * @param null $id
     * @param null $editable_fields
     * @throws Exception
     */
    public function set_section($section, $id = null, $editable_fields = null)
    {
        global $vars, $auth;

        if (!$vars['fields'][$section]) {
            throw new Exception('Section does not exist: ' . $section, E_USER_ERROR);
        }

        $this->section = $section;
        $this->table = underscored($section);
        $this->editable_fields = is_array($editable_fields) ? $editable_fields : array_keys($vars['fields'][$this->section]);

        // deprecated: backcompat in case field names are passed
        foreach ($this->editable_fields as $k => $v) {
            $this->editable_fields[$k] = spaced($v);
        }
        $this->editable_fields = array_unique($this->editable_fields);

        // only admin can edit admin perms
        if (1 != $auth->user['admin'] && in_array('admin', $this->editable_fields)) {
            unset($this->editable_fields[array_search('admin', $this->editable_fields)]);
        }

        $this->field_id = $this->get_id_field($this->section);

        // default id to 1 if no id field
        if (!$id and !in_array('id', $vars['fields'][$this->section])) {
            $id = 1;
        }

        if ($id) {
            $this->id = $id;
            $this->content = $this->get($section, ['id' => $this->id], 1);

            if (!$this->content) {
                $this->id = null;
            }
        } else {
            $this->content = $_GET;
            $this->id = null;
        }
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
                    $option = $this->get_option_label($field);

                    $row = sql_query('SELECT `' . underscored($option) . '` FROM `' . underscored($vars['options'][$field]) . "` WHERE $join_id='" . escape($value) . "'");
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

    private function get_component(string $type)
    {
        switch ($type) {
            case 'int':
                $type = 'integer';
                break;
            case 'parent':
                $type = 'select_parent';
                break;
            case 'page-name':
                $type = 'page_name';
                break;
            case 'phpupload':
                $type = 'upload';
                break;
            case 'phpuploads':
                $type = 'uploads';
                break;
        }

        $class = 'cms\\components\\' . $this->camelize($type);
        if (true === class_exists($class)) {
            return new $class;
        }
    }

    // get name of the id field
    public function get_id_field($section)
    {
        global $vars;
        return in_array('id', $vars['fields'][$section]) ? array_search('id', $vars['fields'][$section]) : 'id';
    }

    public function get_option_label($field)
    {
        global $vars;
        reset($vars['fields'][$vars['options'][$field]]);
        return key($vars['fields'][$vars['options'][$field]]);
    }

    /**
     * @param string $input
     * @param string $separator
     * @return string
     */
    private function camelize(string $input, string $separator = '_'): string
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }

    // get field widget
    public function get_field(string $name, $attribs = '', $placeholder = '', $separator = null, $where = false)
    {
        global $vars;

        if ($vars['fields'][$this->section][spaced($name)]) {
            $name = spaced($name);
        }

        $type = $vars['fields'][$this->section][$name];
        $field_name = underscored($name);
        $value = $this->content[$field_name];

        if ($component = $this->get_component($type)) {
            $readonly = !in_array($name, $this->editable_fields);
            echo $component->field($field_name, $value, ['readonly' => $readonly, 'attribs' => $attribs, 'placeholder' => $placeholder, 'separator' => $separator]);
        }
    }

    // get formatted value
    public function get_value(string $name)
    {
        global $vars;

        $type = $vars['fields'][$this->section][$name];
        $field_name = underscored($name);
        $value = $this->content[$field_name];

        if ($component = $this->get_component($type)) {
            $readonly = !in_array($name, $this->editable_fields);
            $value = $component->value($value, $name);
        }

        return $value;
    }

    // loads the current view
    public function admin()
    {
        global $auth, $vars;

        $option = $_GET['option'] ?: 'index';

        // redirect if logged out
        if ('login' != $option && !$auth->user['admin']) {
            // check table exists
            if (!table_exists($auth->table)) {
                $this->check_table($auth->table, $vars['fields'][$auth->table]);
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

            $_SESSION['request'] = $_SERVER['REQUEST_URI'];
            redirect('/admin?option=login');
        }

        // check permissions
        if ($auth->user['admin'] > 1 && table_exists('cms_privileges')) {
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
        } elseif (in_array($option, ['index', 'login', 'configure', 'upgrade', 'choose_filter'])) {
            $this->template($option . '.php', true);
        } elseif ('index' != $option) {
            $this->default_section($option);
        }
    }

    // send admin notification
    public function notify(string $subject = null, $to = null)
    {
        global $vars, $from_email;

        if (!is_string($to)) {
            $to = $from_email;
        }

        if (!$subject || is_numeric($subject)) {
            $subject = 'New submission to: ' . $this->section;
        }

        $msg = 'New submission to ' . $this->section . ":\n\n";

        $this->set_section($this->section, $this->id);

        foreach ($vars['fields'][$this->section] as $name => $type) {
            $msg .= ucfirst(spaced($name)) . ': ' . $this->get_value($name) . "\n";
        }
        $msg .= "\n" . 'https://' . $_SERVER['HTTP_HOST'] . '/admin?option=' . rawurlencode($this->section) . '&edit=true&id=' . $this->id;

        $msg = nl2br($msg);

        $opts = [
            'subject' => $subject,
            'content' => $msg,
            'to_email' => $to,
        ];

        // reply to
        if ($vars['fields'][$this->section]['email']) {
            $opts['reply_to'] = strip_tags($this->get_value('email'));
        }

        send_mail($opts);
    }

    // handle ajax form submission
    public function submit($notify = null, $other_errors = [])
    {
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
        global $vars;

        if (!is_array($data)) {
            $data = $_POST;
        }

        $errors = $this->trigger_event('beforeValidate', ['data' => $data]);

        if (!is_array($errors)) {
            $errors = [];
        }

        // get table keys
        $table_keys = sql_query('SHOW keys FROM `' . $this->table . '`');
        $keys = [];
        foreach ($table_keys as $v) {
            if (0 == $v['Non_unique']) {
                $keys[$v['Key_name']][] = $v['Column_name'];
            }
        }

        foreach ($vars['fields'][$this->section] as $name => $type) {
            $component = $this->get_component($type);
            $field_name = underscored($name);

            // skip readonly and blank passwords
            if (
                !in_array($field_name, $this->editable_fields) ||
                ($component->preserve_value and '' == $data[$field_name] && $this->id)
            ) {
                continue;
            }

            // check fields
            if (
                ('' != $data[$field_name] && $component && !$component->isValid($data[$field_name])) ||
                (in_array($name, $vars['required'][$this->section]) && '' == $data[$field_name])
            ) {
                $errors[] = $field_name;
                continue;
            }

            //check keys
            $in_use = is_object($strs) ? $strs->inUse : 'in use';
            foreach ($keys as $key => $fields) {
                if (!in_array($field_name, $fields)) {
                    continue;
                }

                $where = [];
                foreach ($fields as $field) {
                    $where[] = '`' . escape($field) . "`='" . escape($data[$field]) . "'";
                }

                // exclude current item
                if ($this->id) {
                    $where[] = '`' . $this->field_id . "`!='" . escape($this->id) . "'";
                }

                $where_str = "WHERE \n" . implode(" AND \n", array_filter($where));

                $row = sql_query('SELECT * FROM `' . $this->table . "`
                    $where_str
                    LIMIT 1
                ", 1);

                if ($row) {
                    foreach ($fields as $field) {
                        $errors[] = $field . ' ' . $in_use;
                    }
                    break;
                }
            }
        }

        return array_values(array_unique($errors));
    }

    // build update query from array
    public function build_query($field_arr, $data)
    {
        global $vars;

        // find null fields
        $column_data = sql_query('SHOW COLUMNS FROM `' . $this->table . '`');
        $null_fields = [];
        foreach ($column_data as $v) {
            if ('YES' == $v['Null']) {
                $null_fields[] = $v['Field'];
            }
        }

        foreach ($field_arr as $name => $type) {
            if (false === in_array($name, $this->editable_fields)) {
                continue;
            }

            $field_name = underscored($name);
            $component = $this->get_component($type);

            // apply field formatting
            $data[$field_name] = $component->formatValue($data[$field_name], $field_name);

            // skip if preserving or not for saving this way
            if (
                false === $data[$field_name] or
                ($component->preserve_value and '' == $data[$field_name] && $this->id)
            ) {
                continue;
            }

            // handle null fields
            $this->query .= "`$field_name`=";
            if (empty($data[$field_name]) && in_array($field_name, $null_fields)) {
                $this->query .= 'NULL';
            } elseif ('coords' == $type) {
                // todo: move to components
                $this->query .= "GeomFromText('POINT(" . escape($data[$field_name]) . ")')";
            } else {
                $this->query .= "'" . escape($data[$field_name]) . "'";
            }
            $this->query .= ",\n";
        }
    }

    // save data, called after set_section
    public function save($data = null)
    {
        global $vars, $auth;

        // default to post data
        if (null === $data) {
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

        //build query
        $this->query = '';
        $this->build_query($vars['fields'][$this->section], $data);
        $this->query = substr($this->query, 0, -2);

        $details = '';
        if ($this->id) {
            $where_str = $this->field_id . "='" . escape($this->id) . "'";

            // remember old state
            $row = sql_query('SELECT * FROM `' . $this->table . "`
                WHERE
                    `id`='" . escape($this->id) . "'
            ", 1);

            sql_query('UPDATE `' . $this->table . '` SET
                ' . $this->query . "
                WHERE 
                    $where_str
            ");

            // find changes
            $updated_row = sql_query('SELECT * FROM `' . $this->table . "`
                WHERE
                    `id`='" . escape($this->id) . "'
            ", 1);

            foreach ($updated_row as $k => $v) {
                if ($row[$k] != $v) {
                    $details .= $k . '=' . $v . "\n";
                }
            }

            $task = 'edit';
        } else {
            sql_query('INSERT IGNORE INTO `' . $this->table . '` SET
                ' . $this->query . '
            ');
            $this->id = sql_insert_id();

            // update fields that require an id
            foreach ($vars['fields'][$this->section] as $name => $type) {
                if (false === in_array($name, $this->editable_fields)) {
                    continue;
                }

                $field_name = underscored($name);
                $component = $this->get_component($type);

                // apply field formatting
                if ($component->id_required) {
                    $data[$field_name] = $component->formatValue($data[$field_name], $field_name);
                }
            }

            $task = 'add';
        }

        // log it
        if (table_exists('cms_logs')) {
            $this->save_log($this->section, $this->id, $task, $details);
        }

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
                    in_array($this->section, $handler['section']) &&
                    $handler['event'] === $event
                ) {
                    return call_user_func_array($handler['handler'], (array) $args);
                }
            }
        }
    }

    public function template($include, $local = false)
    {
        // globals are needed for the page templates
        global $vars, $auth, $shop_enabled, $live_site, $cms_buttons, $message;

        ob_start();
        if ($local) {
            require(dirname(__FILE__) . '/_tpl/' . $include);
        } else {
            require('_tpl/admin/' . $include);
        }
        $include_content = ob_get_contents();
        ob_end_clean();

        // page title used in template.php
        if (!$title && preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $include_content, $matches)) {
            $title = strip_tags($matches[1]);
        }

        // user privileges
        if (table_exists('cms_filters')) {
            $this->filters = sql_query("SELECT * FROM cms_filters WHERE user = '" . escape($auth->user['id']) . "'");
        }

        require(dirname(__FILE__) . '/_tpl/template.php');
        exit;
    }

    public function default_section($option)
    {
        global $vars;

        $this->section = $option;
        $this->table = underscored($option);

        if ($vars['fields'][$this->section]) {
            $this->check_table($this->table, $vars['fields'][$this->section]);
        } else {
            $index = true;
        }

        // actions
        $conditions = $_POST['select_all_pages'] ? $_GET : $_POST['id'];
        switch ($_POST['action']) {
            case 'export':
                $this->export_items($_POST['section'], $conditions, $_POST['select_all_pages']);
                break;
            case 'delete':
                $this->delete_items($_POST['section'], $conditions, $_POST['select_all_pages']);
                break;
        }

        if ($index) {
            $this->template('default_index.php', true);
        } elseif ($_GET['edit']) {
            $this->template('default_edit.php', true);
        } elseif ($_GET['view'] || !in_array('id', $vars['fields'][$this->section])) {
            $this->template('default_view.php', true);
        } else {
            $this->template('default_list.php', true);
        }
    }
}
