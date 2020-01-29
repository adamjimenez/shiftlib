<?php

namespace cms\components;

use cms;
use cms\ComponentInterface;
use Exception;

class Checkboxes extends Select implements ComponentInterface
{
    public $id_required = true;

    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return null;
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @throws Exception
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        /** @var string $name */
        $name = spaced($fieldName);

        $value = [];

        // get options from a section
        if (!is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            if ($this->cms->id) {
                $join_id = $this->cms->get_id_field($name);

                // get preselected values
                $rows = sql_query('SELECT T1.value FROM cms_multiple_select T1
                    INNER JOIN `' . escape(underscored($this->vars['options'][$name])) . "` T2 ON T1.value=T2.$join_id
                    WHERE
                        section='" . escape($this->cms->section) . "' AND
                        field='" . escape($name) . "' AND
                        item='" . $this->cms->id . "'
                ");

                foreach ($rows as $row) {
                    $value[] = $row['value'];
                }
            }

            $this->vars['options'][$name] = $this->get_options($name, false);
        } else {
            if ($this->cms->id) {
                $rows = sql_query("SELECT value FROM cms_multiple_select
                    WHERE
                        section='" . escape($this->cms->section) . "' AND
                        field='" . escape($name) . "' AND
                        item='" . $this->cms->id . "'
                ");

                foreach ($rows as $row) {
                    $value[] = $row['value'];
                }
            }
        }

        $is_assoc = is_assoc_array($this->vars['options'][$name]);

        $parts = [];
        $parts[] = '<ul class="checkboxes">';

        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $is_assoc ? $k : $v;
            $parts[] = '<li><label><input type="checkbox" name="' . $fieldName . '[]" value="' . $val . '" ' . ($options['readonly'] ? 'readonly' : '') . ' ' . (in_array($val, $value) ? 'checked="checked"' : '') . '/>' . $v . '</label></li>';
        }

        $parts[] = '</ul>';
        return implode('', $parts);
    }

    /**
     * @param $value
     * @param string $name
     * @throws Exception
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        $array = [];
        if (!is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            $join_id = $this->cms->get_id_field($name);

            //make sure we get the label from the first array item
            reset($this->vars['fields'][$this->vars['options'][$name]]);

            $rows = sql_query('SELECT `' . underscored(key($this->vars['fields'][$this->vars['options'][$name]])) . '`,T1.value FROM cms_multiple_select T1
                INNER JOIN `' . escape(underscored($this->vars['options'][$name])) . "` T2 ON T1.value = T2.$join_id
                WHERE
                    T1.field='" . escape($name) . "' AND
                    T1.item='" . $this->cms->id . "' AND
                    T1.section='" . $this->cms->section . "'
                GROUP BY T1.value
                ORDER BY T2." . underscored(key($this->vars['fields'][$this->vars['options'][$name]])) . '
            ');

            foreach ($rows as $row) {
                $array[] = '<a href="?option=' . escape($this->vars['options'][$name]) . '&view=true&id=' . $row['value'] . '">' . current($row) . '</a>';
            }
        } else {
            $rows = sql_query("SELECT value FROM cms_multiple_select
                WHERE
                    field='" . escape($name) . "' AND
                    item='" . $this->cms->id . "'
                ORDER BY id
            ");

            $is_assoc = is_assoc_array($this->vars['options'][$name]);
            foreach ($rows as $row) {
                $array[] = $is_assoc ? $this->vars['options'][$name][$row['value']] : current($row);
            }
        }

        return implode('<br>' . "\n", $array);
    }

    /**
     * @param $value
     * @param string|null $fieldName
     * @throws Exception
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        $name = spaced($fieldName);

        if ($this->cms->id) {
            // create NOT IN string
            $value_str = '';
            if (count($value)) {
                foreach ($value as $v) {
                    $value_str .= "'" . escape($v) . "',";
                }
                $value_str = substr($value_str, 0, -1);
                $value_str = 'AND item NOT IN (' . $value_str . ')';
            }

            sql_query("DELETE FROM cms_multiple_select
                WHERE
                    section='" . escape($this->cms->section) . "' AND
                    field='" . escape($name) . "' AND
                    item='" . escape($this->cms->id) . "'
                    $value_str
            ");

            foreach ($value as $v) {
                sql_query("INSERT INTO cms_multiple_select SET
                    section='" . escape($this->cms->section) . "',
                    field='" . escape($name) . "',
                    item='" . escape($this->cms->id) . "',
                    value='" . escape($v) . "'
                ");
            }
        }

        return false;
    }

    /**
     * Generates sql code for use in where statement
     *
     * @param string $fieldName
     * @param $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string
    {
        return null;
    }

    /**
     * @param string $name
     * @param $value
     * @return string
     * @throws Exception
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);

        if (!is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            $this->vars['options'][$name] = $this->get_options(underscored($this->vars['options'][$name]), underscored(key($this->vars['fields'][$this->vars['options'][$name]])));
        }

        $html = [];

        $html[] = '<div>';
        $html[] = '     <label for="' . underscored($name) . '" class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '</div>';
        $html[] = '<div style="max-height: 200px; width: 200px; overflow: scroll">';
        $is_assoc = is_assoc_array($this->vars['options'][$name]);
        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $is_assoc ? $k : $v;
            $html[] = '<label><input type="checkbox" name="' . $field_name . '[]" value="' . $val . '" ' . (in_array($val, $_GET[$field_name]) ? 'checked' : '') . '>' . $v . '</label><br>';
        }
        $html[] = '</div>';
        return implode(' ', $html);
    }
}
