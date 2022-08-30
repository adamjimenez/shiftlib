<?php

class cms
{
    const VERSION = '3.0.2';

    /**
    * @var string
    */
    public $section = '';

    /**
    * @var array
    */
    public $content = [];

    // hide these field types from the list view
    public $hidden_columns = ['password'];

    public $buttons = [];

    public $handlers = [];

    public $file_upload_path = 'uploads/files/';
    
    private $default_users_table =    
        [
            'email' => 'email',
            'password' => 'password',
            'admin' => 'select',
            'date' => 'timestamp',
            'id' => 'id',
            'indexes' => [[
                'name' => 'email',
                'type' => 'unique',
                'fields' => ['email'],
            ]]
        ];

    public function __construct() {}

    public function addButton($buttons) {
        $default_buttons = [[
            'section' => 'email templates',
            'page' => 'view',
            'label' => 'Send Preview',
            'handler' => function () {
                global $auth,
                $cms;

                $content = $cms->get('email templates', $_GET['id']);
                email_template($auth->user['email'], $content['id'], $auth->user);
                $_SESSION['message'] = 'Preview sent';
            }]];
        $this->buttons = array_merge($default_buttons, $buttons);
    }

    public function bind($handlers) {
        $this->handlers = array_merge($this->handlers, $handlers);
    }

    public function getId() {
        return $this->id;
    }

    /**
    * @return string
    */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
    * @return array
    */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
    * @param string $table
    * @param array $fields OR NULL
    * @throws Exception
    */
    public function check_table(string $table, $fields = []) {
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
                
                $auto_increment = $type === 'id' ? 'AUTO_INCREMENT' : '';

                if ($db_field) {
                    $query .= '`' . $name . '` ' . $db_field . ' ' . $auto_increment . ' NOT NULL,';
                }
            }

            sql_query("CREATE TABLE `$table` (
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
    public function form_to_db(string $type) {
        if ($component = $this->get_component($type)) {
            return $component->getFieldSql();
        }

        switch ($type) {
            case 'id':
                case 'separator':
                    break;
                case 'read':
                    case 'deleted':
                        return 'TINYINT(1)';
                        break;
                    default:
                        return "VARCHAR(140)";
                        break;
            }
        }

        // export items to csv
        public function export_items($section, $conditions, $select_all_pages, $columns = []) {
            global $auth,
            $db_connection,
            $vars;

            if (1 !== (int)$auth->user['admin'] && 3 !== (int)$auth->user['privileges'][$section]) {
                $_SESSION['message'] = 'Permission denied';
                return false;
            }

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
            $field_id = 'id';

            foreach ($columns as $k => $v) {
                $columns[$k] = "T_$table." . underscored($v);
            }

            $cols = count($columns) ? implode(',', $columns) : 'T_$table.* ' . $sql['cols'];

            $query = "SELECT " . $cols . "
            FROM `$table` T_$table
            " . $sql['joins'] . '
            ' . $sql['where_str'] . '
            GROUP BY
            T_' . $table . '.' . $field_id . '
            ' . $sql['having_str'];

            $result = mysqli_query($db_connection, $query, MYSQLI_USE_RESULT);

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

            exit;
        }

        public function delete_items($section, $conditions, $select_all_pages = false) // used in admin system
        {
            global $auth;

            if (1 == $auth->user['admin'] || $auth->user['privileges'][$section] > 1) {
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

            if (false === is_array($ids)) {
                $ids = [$ids];
            }

            if (false === $this->trigger_event('beforeDelete', [$ids])) {
                return false;
            }

            $field_id = $this->get_id_field($section);

            $fields = $this->get_fields($section);

            foreach ($ids as $id) {
                // cheeck perms
                $conditions[$field_id] = $id;
                $content = $this->get($section, $conditions);
                if (!$content) {
                    continue;
                }

                if ($fields['deleted']) {
                    $this->set_section($section, $id, ['deleted']);
                    $this->save(['deleted' => 1]);
                } else {
                    sql_query('DELETE FROM `' . escape(underscored($section)) . '`
                    WHERE id = ' . (int)$id . '
                        LIMIT 1
                ');

                    //multiple select items
                    sql_query("DELETE FROM cms_multiple_select
                    WHERE
                        section = '" . escape(underscored($section)) . "' AND
                        item = '$id'
                ");
                }

                // log it
                $this->save_log($section, $id, 'delete');
            }

            $this->trigger_event('delete', [$ids]);
        }

        public function file($file_id) {
            global $auth,
            $vars,
            $auth;

            if (1 != $auth->user['admin'] and !$auth->user['privileges']['uploads'] and $_GET['hash'] !== md5($auth->hash_salt . $file_id)) {
                die('access denied');
            }

            $row = sql_query("SELECT * FROM files
            WHERE
        	    id='" . escape($file_id) . "'
        ", 1) or die('file not found');

            header('Content-type: ' . $row['type']);
            header('Content-Disposition: inline; filename="' . $row['name'] . '"');

            if (!$_GET['w'] && !$_GET['h']) {
                print file_get_contents($this->file_upload_path . $row['id']);
            } else {
                // end configure
                $max_width = $_GET['w'] ?: 320;
                $max_height = $_GET['h'] ?: 240;

                $img = imageorientationfix($this->file_upload_path . $row['id']);
                $img = thumb_img($img, [$max_width, $max_height], false);
                $ext = file_ext($row['name']);

                switch ($ext) {
                    case 'png':
                        imagepng($img);
                        break;
                    case 'gif':
                        imagegif($img);
                        break;
                    default:
                        imagejpeg($img, null, 85);
                        break;
                }
            }
        }

        public function conditions_to_sql($section, $conditions = [], $num_results = null, $cols = null) {
            return $this->conditionsToSql($section, $conditions, $num_results, $cols);
        }

        // create search params, used by get()
        public function conditionsToSql($section, $conditions = [], $num_results = null, $cols = null) {
            global $vars,
            $auth;

            $table = underscored($section);
            $field_id = $this->get_id_field($section);

            $fields = $this->get_fields($section);

            // check for id or page name
            $id = null;
            if (is_numeric($conditions)) {
                $id = $conditions;
                $conditions = ['id' => $id];
                $num_results = 1;
            } elseif (is_string($conditions) && $fields['page name']) {
                $conditions = ['page name' => $conditions];
                $num_results = 1;
            } elseif ($conditions['id']) {
                $id = $conditions['id'];
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
            if ($fields['deleted'] && !isset($conditions['deleted']) && !$id) {
                $where[] = "T_$table.deleted = 0";
            }

            $fields = $this->get_fields($section);
            $joins = '';
            foreach ($fields as $name => $field) {
                $type = $field['type'];

                if (!$type) {
                    continue;
                }

                $field_name = underscored($name);
                $value = $conditions[$field_name];

                switch ($type) {
                    case 'checkboxes':
                        $joins .= ' LEFT JOIN cms_multiple_select S_' . $field_name . ' ON S_' . $field_name . '.item = T_' . $table . '.' . $field_id;

                        if (isset($value) && $value !== '') {
                            // todo: move to component
                            if (false === is_array($value)) {
                                $value = [$value];
                            }

                            $or = [];
                            foreach ($value as $v) {
                                $v = is_array($v) ? $v['value'] : $v;
                                $or[] = 'S_' . $field_name . ".value = '" . escape($v) . "' AND
                                S_" . $field_name . ".field = '" . escape($name) . "' AND
                                S_" . $field_name . ".section='" . $section . "'";
                            }

                            $or_str = implode(' OR ', $or);
                            $where[] = '(' . $or_str . ')';
                        }
                        break;
                    case 'postcode':
                        // todo: move to component and replace with ST_Distance_Sphere when supported (https://jira.mariadb.org/browse/MDEV-13467)
                        if ($value && calc_grids($value) && is_numeric($conditions['func'][$field_name])) {
                            $grids = calc_grids($value);

                            if ($grids) {
                                $cols[] = '
                            (
        						SELECT
        							ROUND(SQRT(POW(Grid_N-' . $grids[0] . ',2)+POW(Grid_E-' . $grids[1] . ",2)) * 0.000621371192)
        							AS distance
        						FROM postcodes
        						WHERE
        							Pcode=
        							REPLACE(SUBSTRING(SUBSTRING_INDEX(T_places.postcode, ' ', 1), LENGTH(SUBSTRING_INDEX(T_places.postcode, ' ', 0)) + 1), ',', '')
        					) AS distance";

                                $having[] = 'distance <= ' . escape($conditions['func'][$field_name]) . '';
                            }
                        }
                        break;
                    default:
                        if (isset($value) && ($value !== '' || $conditions['func'][$field_name])) {
                            if ($component = $this->get_component($type)) {
                                // deprecated
                                if (is_array($conditions) && $conditions['end'][$field_name]) {
                                    $conditions['func'][$field_name] = ['end' => $conditions['end'][$field_name]];
                                }
                                
                                
                                $where[] = $component->conditionsToSql($field_name, $value, $conditions['func'][$field_name], "T_$table.");
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
                    //$having_or = [];

                    foreach ($fields as $name => $field) {
                        $type = $field['type'];

                        if (!in_array($type, ['text', 'textarea', 'editor', 'email', 'mobile', 'select', 'id'])) {
                            continue;
                        }

                        $value = str_replace('*', '%', $word);

                        if ('select' == $type) {
                            if (is_string($vars['options'][$name])) {
                                //$having_or[] = underscored($name) . "_label LIKE '%" . escape($value) . "%'";
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

                    // add 'or' array to 'having' array
                    /*
                if (count($having_or)) {
                    $having_or_str = implode(' OR ', $having_or);
                    $having[] = '(' . $having_or_str . ')';
                }
                */
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
            if (is_array($where) && count($where)) {
                $where_str = "WHERE \n" . implode(" AND \n", array_filter($where));
            }

            // create having string
            $having_str = '';
            if (is_array($having) && count($having)) {
                $having_str = "HAVING \n" . implode(" AND \n", array_filter($having));
            }

            $keys = [];
            foreach ($fields as $name => $field) {
                if (in_array($field['type'], ['select', 'combo', 'radio'])) {
                    $keys[] = $name;
                }
            }

            // create joins
            if (count($keys)) {
                foreach ($keys as $key) {
                    if (false === is_array($vars['options'][$key])) {
                        $option_table = underscored($vars['options'][$key]);

                        if (!$option_table) {
                            die('missing options array value for: ' . $key);
                        }

                        $option = $this->get_option_label($vars['options'][$key]);

                        $cols[] = "(SELECT " . underscored($option) . "  FROM ".$option_table." WHERE id = T_$table." . underscored($key) . ") AS '" . underscored($key) . "_label'";
                    }
                }
            }

            $cols = implode(', ', (array)$cols);

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
        public function get($section, $conditions = null, $num_results = null, $order = null, $asc = true, $prefix = null, $return_query = false) {
            global $vars;

            $table = underscored($section);

            // set a default prefix to prevent pagination clashing
            if (!$prefix) {
                $prefix = $table;
            }

            // select columns
            // todo move to components
            $fields = $this->get_fields($section);

            $cols = [];
            foreach ($fields as $name => $field) {
                $type = $field['type'];

                if (!$type || in_array($type, ['checkboxes', 'separator'])) {
                    continue;
                }

                $component = $this->get_component($type);
                $col = $component->getColSql(underscored($name), "T_$table.");

                $cols[] = "\t" . $col . ' AS `' . underscored($name) . '`' . "\n";
            }

            // backcompat
            $no_id = is_array($vars['fields'][$section]) && count($vars['fields'][$section]) && !$vars['fields'][$section]['id'];

            // select id column
            if ($fields['id'] && false === $no_id) {
                $cols[] = "\tT_$table.id";
            } else {
                $num_results = 1;
            }

            // determine sort order
            if (is_array($conditions) && ($conditions['s'] || $conditions['w'])) {
                $word = $conditions['w'] ?: $conditions['s'];

                // find first text field
                $col = null;
                foreach ($fields as $name => $field) {
                    if ($field['type'] === 'text') {
                        $col = underscored($name);
                        break;
                    }
                }

                if ($col) {
                    if ($order) {
                        $order = ', ' . $order;
                    }

                    $order = "
                    CASE
                        WHEN T_".$table.".".$col." LIKE '".escape($word)."' THEN 1
                        WHEN T_".$table.".".$col." LIKE '".escape($word)."%' THEN 2
                        WHEN T_".$table.".".$col." LIKE '%".escape($word)."' THEN 4
                        ELSE 3
                    END
                " . $order;
                }
            }

            if (!$order) {
                $field_date = $fields['date'] ? 'date' : '';
                if (!$field_date) {
                    $field_date = $fields['timestamp'] ? 'timestamp' : '';
                }

                if ($fields['position']) {
                    $order = 'T_' . $table . '.position';
                } elseif ($field_date) {
                    $order = 'T_' . $table . '.' . underscored($field_date);
                    $asc = false;
                } else {
                    $label = current($fields);
                    $type = $label['type'];

                    // order options by value instead of key
                    if (in_array($type, ['select', 'combo', 'radio']) && !is_array($vars['opts'][$label])) {
                        $key = $this->get_option_label($label);
                        //$order = 'T_' . underscored($label) . '.' . underscored($key);
                        $order = underscored($label) . '_label';
                    } elseif ($label) {
                        $order = "T_$table." . $label['column'];
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
            } else if ($fields['id']) {
                $group_by_str = "GROUP BY T_$table.id";
            }

            $query = "SELECT
            $cols
            FROM `$table` T_$table
                $joins
            $where_str
            $group_by_str
            $having_str
            ";

            //debug($query);

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
            foreach ($fields as $name => $field) {
                $type = $field['type'];

                if ('select_multiple' === $type) {
                    foreach ($content as $k => $v) {
                        $content[$k][underscored($name)] = json_decode($v[$name], true);
                    }
                    continue;
                }

                if ('files' === $type) {
                    if ($v[$name]) {
                        foreach ($content as $k => $v) {
                            $content[$k][underscored($name)] = explode("\n", $v[$name]);
                        }
                    }
                    continue;
                }

                if ('checkboxes' !== $type) {
                    continue;
                }

                foreach ($content as $k => $v) {

                    if (false === is_array($vars['options'][$name]) && $vars['options'][$name]) {
                        $join_id = $this->get_id_field($name);
                        $key = $this->get_option_label($name);

                        $rows = sql_query("SELECT `" . underscored($key) . '`,T1.value FROM cms_multiple_select T1
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
        public function set_section($section, $id = null, $editable_fields = null, $select = true) {
            global $vars,
            $auth;

            $this->section = $section;
            $this->table = underscored($section);
            $fields = $this->get_fields($this->section);

            $this->editable_fields = is_array($editable_fields) ? $editable_fields : array_keys($fields);

            // deprecated: backcompat in case field names are passed
            foreach ($this->editable_fields as $k => $v) {
                $this->editable_fields[$k] = spaced($v);
            }
            $this->editable_fields = array_unique($this->editable_fields);

            // only admin can edit admin perms
            if (1 != $auth->user['admin'] && in_array('admin', $this->editable_fields) && $auth->user['privileges']['cms privileges'] < 2) {
                unset($this->editable_fields[array_search('admin', $this->editable_fields)]);
            }

            $this->field_id = 'id';

            // default id to 1 if no id field
            if ($id || !$fields['id']) {
                $this->id = $id;

                if ($select) {
                    $this->content = $this->get($section, ['id' => $this->id], 1);

                    if (!$this->content) {
                        $this->id = null;
                    }
                }
            } else {
                $this->content = $_GET;
                $this->id = null;
            }
        }

        // get label for current content
        public function get_label() {
            $field = $this->get_label_field($this->section);

            $value = $this->content[$field['column']];

            return truncate($value);
        }

        // get field used for labels
        public function get_label_field($section) {
            $fields = $this->get_fields($section);

            // find first text field
            $label = null;
            foreach ($fields as $name => $field) {
                if ($field['type'] === 'text') {
                    $label = $field;
                    break;
                }
            }

            return $label;
        }

        /**
        * @param string $type
        * @return \cms\ComponentInterface
        */
        private function get_component(string $type): cms\ComponentInterface
        {
            global $cms,
            $auth,
            $vars;

            $type = $this->get_component_name($type);

            $class = 'cms\\components\\' . $this->camelize($type);
            if (true === class_exists($class)) {
                return new $class($cms, $auth, $vars);
            }
        }

        // get component name, needed for backward compatibility
        public function get_component_name(string $type): string
        {
            switch ($type) {
                case 'int':
                    return 'integer';
                    case 'number':
                        return 'decimal';
                        case 'parent':
                            return 'select_parent';
                            case 'page-name':
                                return 'page_name';
                                case 'phpupload':
                                    return 'upload';
                                    case 'phpuploads':
                                        return 'uploads';
                                        case 'enum':
                                            return 'select';
                                    }

                                    return $type;
                            }

                            // get name of the id field
                            public function get_id_field($section) {
                                return 'id';
                        }

                        public function get_option_label($field) {
                            global $vars;

                            $fields = $this->get_fields($field);

                            foreach ($fields as $name => $field) {
                                if (in_array($field['type'], ['text', 'email', 'id'])) {
                                    return $name;
                                }
                            }

                            return 'id';
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
                public function get_field(string $name, $attribs = '', $placeholder = '', $separator = null, $where = false): string
                {
                    global $vars;

                    $fields = $this->get_fields($this->section);
                    $field = $fields[spaced($name)];

                    if (!$field) {
                        return false;
                    }

                    $type = $field['type'];
                    $field_name = $field['column'];
                    $value = $this->content[$field_name];

                    if ($component = $this->get_component($type)) {
                        $readonly = !in_array($name, $this->editable_fields);
                        return $component->field($field_name, $value, ['readonly' => $readonly, 'attribs' => $attribs, 'placeholder' => $placeholder, 'separator' => $separator]);
                    }

                    return '';
            }

            // get formatted value
            public function get_value(string $name) {
                global $vars;

                $fields = $this->get_fields($this->section);
                $field = $fields[spaced($name)];

                $type = $field['type'];

                if (!$type) {
                    trigger_error('Field does not exist: ' . $this->section . ' - ' . $name, E_ERROR);
                    return false;
                }

                $field_name = underscored($name);
                $value = $this->content[$field_name];

                if ($component = $this->get_component($type)) {
                    $readonly = !in_array($name, $this->editable_fields);
                    $value = $component->value($value, $name);
                }

                return $value;
            }

            public function check_permissions() {
                global $auth;

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
            }

            // loads the current view
            public function admin() {
                global $auth,
                $vars;

                $option = $_GET['option'] ?: 'index';

                // redirect if logged out
                if (!in_array($option, ['login', 'file']) && !$auth->user['admin']) {
                    // check table exists
                    if (!table_exists($auth->table)) {
                        $this->check_table($auth->table, $this->default_users_table);
                    }

                    // check admin user exists
                    $row = sql_query('SELECT id FROM ' . $auth->table . ' LIMIT 1', 1);
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

                $this->check_permissions();

                if (file_exists('_tpl/admin/' . underscored($option) . '.php')) {
                    $this->template(underscored($option) . '.php');
                } elseif (in_array($option, ['index', 'login', 'configure', 'upgrade', 'choose_filter'])) {
                    $this->template($option . '.php', true);
                } elseif ('file' == $option) {
                    $this->file($_GET['f']);
                } elseif ('index' != $option) {
                    $this->default_section($option);
                }
            }

            // send admin notification
            public function notify(string $subject = null, $to = null) {
                global $vars,
                $from_email;

                if (!$this->id) {
                    return false;
                }

                if (!is_string($to)) {
                    $to = $from_email;
                }

                if (!$subject || is_numeric($subject)) {
                    $subject = 'New submission to: ' . $this->section;
                }

                $msg = 'New submission to ' . $this->section . ":\n\n";

                $this->set_section($this->section, $this->id);

                $fields = $this->get_fields($this->section);

                foreach ($fields as $name => $field) {
                    $type = $field['type'];

                    if (in_array($type, ['file', 'files'])) {
                        $value = $this->content[$name];

                        if ($value) {
                            $files = ($type === 'file') ? [$value] : explode("\n", $value);

                            foreach ($files as $file_id) {
                                $row = sql_query("SELECT * FROM files
                            WHERE
                        	    id='" . escape($file_id) . "'
                        ", 1) or die('file not found');

                                $attachments[] = [
                                    'name' => $row['name'],
                                    'path' => $this->file_upload_path . $row['id']
                                ];
                            }
                        }

                        $msg .= ucfirst(spaced($name)) . ': ' . $this->content[$name] . "\n";
                    } else {
                        $msg .= ucfirst(spaced($name)) . ': ' . $this->get_value($name) . "\n";
                    }
                }
                $msg .= "\n" . 'https://' . $_SERVER['HTTP_HOST'] . '/admin?option=' . rawurlencode($this->section) . '&edit=true&id=' . $this->id;

                $msg = nl2br($msg);

                $opts = [
                    'subject' => $subject,
                    'content' => $msg,
                    'to_email' => $to,
                    'attachments' => $attachments,
                ];

                // reply to
                if ($fields['email']) {
                    $opts['reply_to'] = strip_tags($this->get_value('email'));
                }

                send_mail($opts);
            }

            // handle ajax form submission
            public function submit($options = [], $other_errors = []) {
                // backcompat
                if ($options === true) {
                    $options = ['notify' => true];
                }

                if (!isset($options['save'])) {
                    $options['save'] = true;
                }

                $errors = $this->validate($_POST, $options['recaptcha']);

                if (is_array($other_errors)) {
                    $errors = array_values(array_unique(array_merge($errors, $other_errors)));
                }

                //handle validation
                if (count($errors)) {
                    //validateion failed
                    die(json_encode($errors));
                } elseif ($_POST['validate'] && $options['validate'] !== false) {
                    //validation passed
                    die('1');
                }

                if ($options['recaptchav3']) {
                    if (!$this->verifyRecaptcha($_POST['g-recaptcha-response'])) {
                        return false;
                    }
                }

                if ($options['save']) {
                    $this->id = $this->save();
                }

                if ($options['notify']) {
                    $this->notify(null, $options['notify']);
                }

                return $this->id ?: true;
            }

            function verifyRecaptcha($token) {
                global $auth_config;

                if ($auth_config['recaptcha_secret']) {
                    if (!$token) {
                        return false;
                    } else {
                        $params = [
                            'secret' => $auth_config['recaptcha_secret'],
                            'response' => $token
                        ];

                        $verify = curl_init();
                        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
                        curl_setopt($verify, CURLOPT_POST, true);
                        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($params));
                        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
                        $response = curl_exec($verify);
                        $json = json_decode($response, true);

                        $this->score = $json['score'];

                        if ($json['success'] != 1) {
                            return false;
                        }

                        return true;
                    }
                }
            }

            public function get_fields($section) {
                global $vars;

                if (!$section) {
                    return false;
                }

                $rows = sql_query("SHOW FULL COLUMNS FROM `" . escape(underscored($section)) . "`");

                $fields = [];
                foreach ($rows as $row) {
                    $field_name = $row['Field'];
                    $name = spaced($field_name);

                    if ($row['Comment']) {
                        $parts = explode('|', $row['Comment']);
                        $type = $parts[0];
                        $required = $parts[1];
                        $label = $parts[2];
                    } else {
                        $type = $vars["fields"][$section][$name];
                        $required = in_array($name, (array)$vars["required"][$section]);
                        $label = $vars["label"][$section][$name];
                    }

                    if (!$type) {
                        if ($name == 'id') {
                            $type = 'id';
                        }
                    }

                    if ($type) {
                        $fields[$name] = [
                            'column' => $field_name,
                            'type' => $type,
                            'required' => $required,
                            'label' => $label,
                        ];
                    }
                }

                return $fields;
            }

            // validate fields before saving
            public function validate($data = null, $recaptcha = false) {
                global $vars,
                $auth;

                if (false === is_array($data)) {
                    $data = $_POST;
                }

                $errors = $this->trigger_event('beforeValidate', ['data' => $data]);

                if (false === is_array($errors)) {
                    $errors = [];
                }

                // get table keys
                $table_keys = sql_query('SHOW keys FROM `' . $this->table . '`');
                $keys = [];
                foreach ($table_keys as $v) {
                    if (0 == $v['Non_unique']) {
                        $keys[$v['Key_name']][] = $v['column'];
                    }
                }

                $fields = $this->get_fields($this->section);

                foreach ($fields as $name => $field) {
                    if (!$field['type']) {
                        continue;
                    }

                    $component = $this->get_component($field['type']);
                    $field_name = underscored($name);

                    // skip readonly and blank passwords
                    if (
                        !in_array($name, $this->editable_fields) ||
                        ($component->preserveValue && '' == $data[$field_name] && $this->id)
                    ) {
                        continue;
                    }

                    // check admin field
                    if ($name === 'admin' && $data[$name] && $data[$name] < $auth->user['admin']) {
                        $errors[] = $name . ' permission denied';
                    }

                    // check fields
                    if (
                        ('' != $data[$field_name] && $component && !$component->isValid($data[$field_name])) ||
                        ($field['required'] && '' == $data[$field_name] && !count((array)$_FILES[$field_name]))
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

                if ($recaptcha) {
                    if (!$this->verifyRecaptcha($data['g-recaptcha-response'])) {
                        $errors[] = 'recaptcha';
                    }
                }

                return array_values(array_unique($errors));
            }

            // build update query from array
            public function build_query($fields, $data) {
                global $vars;

                // find null fields
                $column_data = sql_query('SHOW COLUMNS FROM `' . $this->table . '`');
                $null_fields = [];
                foreach ($column_data as $v) {
                    if ('YES' == $v['Null']) {
                        $null_fields[] = $v['Field'];
                    }
                }

                foreach ($fields as $name => $field) {
                    $type = $field['type'];

                    if (false === in_array($name, $this->editable_fields)) {
                        continue;
                    }

                    $field_name = underscored($name);
                    $component = $this->get_component($type);

                    // apply field formatting
                    $data[$field_name] = $component->formatValue($data[$field_name], $field_name);

                    // skip if preserving or not for saving this way
                    if (
                        false === $data[$field_name] ||
                        ($component->preserveValue && '' == $data[$field_name] && $this->id)
                    ) {
                        continue;
                    }

                    // handle null fields
                    $query .= "`$field_name`=";
                    if (empty($data[$field_name]) && in_array($field_name, $null_fields)) {
                        $query .= 'NULL';
                    } elseif ('coords' == $type) {
                        // todo: move to components
                        $query .= "GeomFromText('POINT(" . escape($data[$field_name]) . ")')";
                    } else {
                        $query .= "'" . escape($data[$field_name]) . "'";
                    }
                    $query .= ",\n";
                }

                return substr($query, 0, -2);
            }

            // save data, called after set_section
            public function save($data = null) {
                global $vars,
                $auth;

                // default to post data
                if (null === $data) {
                    $data = $_POST;
                }

                if ($this->score) {
                    $data['score'] = $this->score;

                    if ($this->score < $auth->recaptcha_threshold) {
                        return false;
                    }
                }

                // fire event
                $result = $this->trigger_event('beforeSave', ['data' => $data]);

                if ($result === false) {
                    return false;
                }

                if (is_array($result)) {
                    $data = $result;
                }

                // force save data to match privileges
                foreach ($auth->user['filters'][$this->section] as $k => $v) {
                    $data[$k] = $v;
                }

                //build query
                $fields = $this->get_fields($this->section);
                $this->query = $this->build_query($fields, $data);

                $details = '';
                $do_update = $this->id ? true : false;
                $row = null;
                
                if (!$fields['id']) {
                    $row = sql_query('SELECT * FROM `' . $this->table . "`", 1);
                    $do_update = $row ? true : false;
                }
                
                if ($do_update) {
                    $whereStr = $fields['id'] ? "WHERE id = '" . (int)$this->id . "'" : '';

                    // remember old state
                    if (!$row) {
                        $row = sql_query('SELECT * FROM `' . $this->table . "`
                            $whereStr
                        ", 1);
                    }

                    sql_query('UPDATE `' . $this->table . '` SET
                        ' . $this->query . "
                        $whereStr
                    ");

                    // find changes
                    $updated_row = sql_query('SELECT * FROM `' . $this->table . "`
                            $whereStr
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
                    $fields = $this->get_fields($this->section);

                    foreach ($fields as $name => $field) {
                        if (false === in_array($name, $this->editable_fields)) {
                            continue;
                        }

                        $fieldName = underscored($name);
                        $component = $this->get_component($field['type']);

                        // apply field formatting
                        if ($component->idRequired) {
                            $data[$fieldName] = $component->formatValue($data[$fieldName], $fieldName);
                        }
                    }

                    $task = 'add';
                }

                // log it
                $this->save_log($this->section, $this->id, $task, $details);

                $this->trigger_event('save', [$this->id, $data]);
                $this->saved = true;

                return $this->id;
            }

            public function save_log($section, $id, $task, $details = '') {
                global $auth;


                if (!table_exists('cms_logs')) {
                    return;
                }

                sql_query("INSERT INTO cms_logs SET
                    user = '" . $auth->user['id'] . "',
                    section = '" . escape($section) . "',
                    item = '" . escape($id) . "',
                    task = '" . $task . "',
                    details = '" . escape($details) . "'
                ");
            }

            public function trigger_event($event, $args) {
                if (is_array($this->handlers)) {
                    foreach ($this->handlers as $handler) {
                        if (false === is_array($handler['section'])) {
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

            public function template($include, $local = false) {
                // globals are needed for the page templates
                global $vars,
                $auth,
                $shop_enabled,
                $live_site,
                $message;

                ob_start();
                if ($local) {
                    require(__DIR__ . '/_tpl/' . $include);
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

                require(__DIR__ . '/_tpl/template.php');
                exit;
            }

            public function default_section($option) {
                global $vars;

                $this->section = $option;
                $this->table = underscored($option);

                $fields = $this->get_fields($this->section);

                if (!$fields) {
                    $index = true;
                }

                // actions
                $conditions = $_POST['select_all_pages'] ? $_GET : $_POST['id'];
                switch ($_POST['action']) {
                    case 'export':

                        // convert json column indexes into array of column names
                        $indexes = json_decode($_POST['columns']);
                        array_shift($indexes);
                        array_shift($indexes);
                        array_shift($indexes);

                        $columns = [];
                        $i = 0;
                        foreach ($fields as $name => $field) {
                            $type = $field['type'];

                            if (in_array($type, $this->hidden_columns)) {
                                continue;
                            }

                            if ($indexes[$i]) {
                                $columns[] = $name;
                            }
                            $i++;
                        }

                        // add id column
                        if ($i) {
                            array_unshift($columns, 'id');
                        }

                        $this->export_items($_POST['section'], $conditions, $_POST['select_all_pages'], $columns);
                        break;
                    case 'delete':
                        $this->delete_items($_POST['section'], $conditions, $_POST['select_all_pages']);
                        break;
                }

                if ($index) {
                    $this->template('default_index.php', true);
                } elseif ($_GET['edit']) {
                    $this->template('default_edit.php', true);
                } elseif ($_GET['view'] || !$fields['id']) {
                    $this->template('default_view.php', true);
                } else {
                    $this->template('default_list.php', true);
                }
            }
        }