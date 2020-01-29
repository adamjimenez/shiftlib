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
        $name = spaced($fieldName);

        $parts = [];
        if (!is_array($this->vars['options'][$name]) and in_array('parent', $this->vars['fields'][$this->vars['options'][$name]])) {
            $parts[] = '<div class="chained" data-name="' . $fieldName . '" data-section="' . $this->vars['options'][$name] . '" data-value="' . $value . '"></div>';
        } else {
            if (!is_array($this->vars['options'][$name])) {
                $conditions = [];
                foreach ($this->auth->user['filters'][$this->cms->section] as $k => $v) {
                    $conditions[$k] = $v;
                }

                $this->vars['options'][$name] = $this->get_options($name, false);
            }

            $parts[] = '<select name="' . $fieldName . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . '>';
            $parts[] = '<option value="">' . $options['placeholder'] ?: 'Choose' . '</option>';
            $parts[] = html_options($this->vars['options'][$name], $value);
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
        if (!isset($this->vars['options'][$name])) {
            return null;
        }

        // get options from a section
        if (!is_array($this->vars['options'][$name])) {
            // get section table name
            $table = underscored($this->vars['options'][$name]);

            // get first field from section as we will use this for the option labels
            reset($this->vars['fields'][$this->vars['options'][$name]]);
            $field = key($this->vars['fields'][$this->vars['options'][$name]]);
        
            $cols = '`' . underscored($field) . '`';

            // sort by position if available or fall back to field order
            $order = in_array('position', $this->vars['fields'][$this->vars['options'][$name]]) ? 'position' : $field;

            $parent_field = array_search('parent', $this->vars['fields'][$this->vars['options'][$name]]);

            if (false !== $parent_field) {
                // if we have a parent field than get an indented list of options
                $options = $this->get_children($this->vars['options'][$name], $parent_field);
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

            $this->vars['options'][$name] = $options;
        }

        return $this->vars['options'][$name];
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
        reset($this->vars['fields'][$section]);
        $label = key($this->vars['fields'][$section]);

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
            if ($row['id'] === $this->cms->id and $section === $this->cms->section) {
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
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if (!is_array($this->vars['options'][$name])) {
            if ('0' == $value) {
                $value = '';
            } else {
                $value = '<a href="?option=' . escape($this->vars['options'][$name]) . '&view=true&id=' . $value . '">' . $this->cms->content[underscored($name) . '_label'] . '</a>';
            }
        } else {
            if (is_assoc_array($this->vars['options'][$name])) {
                $value = $this->vars['options'][$name][$value];
            }
        }

        return $value ?: '';
    }

    /**
     * @param string $fieldName
     * @param mixed $value
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
     * @param mixed $value
     * @throws Exception
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);
        $options = $this->vars['options'][$name];
        if (!is_array($this->vars['options'][$name])) {
            reset($this->vars['fields'][$this->vars['options'][$name]]);

            $conditions = [];
            foreach ($this->auth->user['filters'][$this->vars['options'][$name]] as $k => $v) {
                $conditions[$k] = $v;
            }

            $field = key($this->vars['fields'][$this->vars['options'][$name]]);
            $table = underscored($this->vars['options'][$name]);
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
