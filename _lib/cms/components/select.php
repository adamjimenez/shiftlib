<?php
namespace cms;

class select extends component
{
	public $field_sql = "VARCHAR( 64 ) NOT NULL DEFAULT ''";
	

    // get select options
    public function get_options(string $name, $where = false)
    {
        global $vars;

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

            $cols = '`' . underscored($field) . '`';

            //sortable
            $order = in_array('position', $vars['fields'][$vars['options'][$name]]) ? 'position' : $field;

            if (in_array('language', $vars['fields'][$vars['options'][$name]])) {
                $where_str = '';
                if ($where) {
                    $where_str = 'AND ' . $where;
                }

                $language = 'en';

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
        }

        return $vars['options'][$name];
    }

    // get parent fields child rows
    public function get_children($section, $parent_field, int $parent = 0, int $depth = 0)
    {
        global $vars, $cms;

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
            if ($row['id'] === $cms->id and $section === $cms->section) {
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
	
	function field($field_name, $value = '', $options = []) {
		global $vars, $cms;
		
		$name = $field_name;

        if (!is_array($vars['options'][$name]) and in_array('parent', $vars['fields'][$vars['options'][$name]])) {
            ?>
        <div class="chained" data-name="<?=$field_name; ?>" data-section="<?=$vars['options'][$name]; ?>" data-value="<?=$value; ?>"></div>
        <?php
        } else {
            if (!is_array($vars['options'][$name])) {
                global $auth;
                
                $conditions = [];
                foreach ($auth->user['filters'][$cms->section] as $k => $v) {
                    $conditions[$k] = $v;
                }
                
                $vars['options'][$name] = $this->get_options($name, $where);
            } ?>
        <select name="<?=$field_name; ?>" <?php if ($option['readonly']) { ?>disabled<?php } ?> <?=$options['attribs']; ?>>
        <option value=""><?=$placeholder ?: 'Choose'; ?></option>
            <?=html_options($vars['options'][$name], $value); ?>
        </select>
        <?php
        }
	}
	
	function value($value, $name) {
		global $vars;
		
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
        }
        
        return $value;
	}
}