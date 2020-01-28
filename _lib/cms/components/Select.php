<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;
use Exception;

class Select extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return "VARCHAR( 64 ) NOT NULL DEFAULT ''";
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        global $vars, $cms, $auth;

        $name = spaced($fieldName);

        $parts = [];
        if (!is_array($vars['options'][$name]) and in_array('parent', $vars['fields'][$vars['options'][$name]])) {
            $parts[] = '<div class="chained" data-name="' . $fieldName . '" data-section="' . $vars['options'][$name] . '" data-value="' . $value . '"></div>';
        } else {
            if (!is_array($vars['options'][$name])) {
                $conditions = [];
                foreach ($auth->user['filters'][$cms->section] as $k => $v) {
                    $conditions[$k] = $v;
                }

                $vars['options'][$name] = $this->get_options($name, false);
            }

            $parts[] = '<select name="' . $fieldName . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . '>';
            $parts[] = '<option value="">' . $options['placeholder'] ?: 'Choose' . '</option>';
            $parts[] = html_options($vars['options'][$name], $value);
            $parts[] = '</select>';
        }

        return implode('', $parts);
    }

    /**
     * Get parent fields child rows
     *
     * @param string $name
     * @param string $where
     * @throws Exception
     * @return array
     */
    public function get_options(string $name, $where = null)
    {
        global $vars;

        if (!isset($vars['options'][$name])) {
            return null;
        }

        // get options from a section
        if (!is_array($vars['options'][$name])) {
            // get section table name
            $table = underscored($vars['options'][$name]);

            // get first field from section as we will use this for the option labels
            reset($vars['fields'][$vars['options'][$name]]);
            $field = key($vars['fields'][$vars['options'][$name]]);
        
            $cols = '`' . underscored($field) . '`';

            // sort by position if available or fall back to field order
            $order = in_array('position', $vars['fields'][$vars['options'][$name]]) ? 'position' : $field;

            $parent_field = array_search('parent', $vars['fields'][$vars['options'][$name]]);

            if (false !== $parent_field) {
                // if we have a parent field than get an indented list of options
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

            $vars['options'][$name] = $options;
        }

        return $vars['options'][$name];
    }

    /**
     * @param $section
     * @param $parent_field
     * @param int $parent
     * @param int $depth
     * @throws Exception
     * @return array
     */
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

    /**
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        global $vars, $cms;

        if (!is_array($vars['options'][$name])) {
            if ('0' == $value) {
                $value = '';
            } else {
                $value = '<a href="?option=' . escape($vars['options'][$name]) . '&view=true&id=' . $value . '">' . $cms->content[underscored($name) . '_label'] . '</a>';
            }
        } else {
            if (is_assoc_array($vars['options'][$name])) {
                $value = $vars['options'][$name][$value];
            }
        }

        return $value ?: '';
    }

    /**
     * @param string $fieldName
     * @param $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string
    {
        if (is_array($value)) {
            $or = '(';
            foreach ($value as $k => $v) {
                $or .= $tablePrefix . $fieldName . " LIKE '" . escape($v) . "' OR ";
            }
            $or = substr($or, 0, -4);
            $or .= ')';

            return $or;
        }
        return $tablePrefix . $fieldName . " LIKE '" . escape($value) . "'";
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        global $vars, $auth;

        $field_name = underscored($name);
        $options = $vars['options'][$name];
        if (!is_array($vars['options'][$name])) {
            reset($vars['fields'][$vars['options'][$name]]);

            $conditions = [];
            foreach ($auth->user['filters'][$vars['options'][$name]] as $k => $v) {
                $conditions[$k] = $v;
            }

            $field = key($vars['fields'][$vars['options'][$name]]);
            $table = underscored($vars['options'][$name]);
            $cols = '`' . underscored($field) . '` AS `' . underscored($field) . '`' . "\n";
            $rows = sql_query("SELECT $cols, id FROM $table ORDER BY `" . underscored($field) . '`');

            $options = [];
            foreach ($rows as $v) {
                $options[$v['id']] = current($v);
            }
        }
        $html = [];
        $html[] = '<div>';
        $html[] = ucfirst($name);
        $html[] = '</div>';
        $html[] = '<select name="' . $name . '[]" multiple size="4" class="form-control">';
        $html[] = html_options($options, $_GET[$field_name]);
        $html[] = '</select>';
        $html[] = '<br>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
