<?php

namespace cms\components;

use cms\ComponentInterface;
use Exception;

class Checkboxes extends Select implements ComponentInterface
{
    public $idRequired = true;

    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TINYINT(1)';
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
        if (false === is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            if ($this->cms->id) {
                $joinId = $this->cms->get_id_field($name);

                // get preselected values
                $rows = sql_query('SELECT T1.value FROM cms_multiple_select T1
                    INNER JOIN `' . escape(underscored($this->vars['options'][$name])) . "` T2 ON T1.value=T2.$joinId
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

        $isAssoc = is_assoc_array($this->vars['options'][$name]);

        $parts = [];
        $parts[] = '<ul class="checkboxes">';

        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $isAssoc ? $k : $v;
            $parts[] = '<li><label><input type="checkbox" name="' . $fieldName . '[]" value="' . $val . '" ' . ($options['readonly'] ? 'readonly' : '') . ' ' . (in_array($val, $value) ? 'checked="checked"' : '') . '>&nbsp;<span>' . $v . '</span></label></li>';
        }

        $parts[] = '</ul>';
        return implode('', $parts);
    }

    /**
     * @param mixed $value
     * @param string $name
     * @throws Exception
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        $array = [];
        if (false === is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            $joinId = $this->cms->get_id_field($name);

            //make sure we get the label from the first array item
            reset($this->vars['fields'][$this->vars['options'][$name]]);

            $rows = sql_query('SELECT `' . underscored(key($this->vars['fields'][$this->vars['options'][$name]])) . '`,T1.value FROM cms_multiple_select T1
                INNER JOIN `' . escape(underscored($this->vars['options'][$name])) . "` T2 ON T1.value = T2.$joinId
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

            $isAssoc = is_assoc_array($this->vars['options'][$name]);
            foreach ($rows as $row) {
                $array[] = $isAssoc ? $this->vars['options'][$name][$row['value']] : current($row);
            }
        }

        return implode('<br>' . "\n", $array);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @throws Exception
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        $name = spaced($fieldName);

        if ($this->cms->id) {
            // create NOT IN string
            $valueStr = '';
            if (count($value)) {
                foreach ($value as $v) {
                    $valueStr .= "'" . escape($v) . "',";
                }
                $valueStr = substr($valueStr, 0, -1);
                $valueStr = 'AND value NOT IN (' . $valueStr . ')';
            }

            sql_query("DELETE FROM cms_multiple_select
                WHERE
                    section='" . escape($this->cms->section) . "' AND
                    field='" . escape($name) . "' AND
                    item='" . escape($this->cms->id) . "'
                    $valueStr
            ");

            foreach ($value as $v) {
				if (!strlen($v)) {
					continue;
				}
                
                sql_query("INSERT IGNORE INTO cms_multiple_select SET
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
     * @param mixed $value
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
     * @param mixed $value
     * @throws Exception
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $fieldName = underscored($name);

        if (false === is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            $this->vars['options'][$name] = $this->get_options($name);
        }

        $html = [];

        $html[] = '<div>';
        $html[] = '     <label for="' . underscored($name) . '" class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '</div>';
        $html[] = '<div style="max-height: 200px; width: 200px; overflow: scroll">';
        $isAssoc = is_assoc_array($this->vars['options'][$name]);
        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $isAssoc ? $k : $v;
            $html[] = '<label><input type="checkbox" name="' . $fieldName . '[]" value="' . $val . '" ' . (in_array($val, $_GET[$fieldName]) ? 'checked' : '') . '>&nbsp;' . $v . '</label><br>';
        }
        $html[] = '</div>';
        return implode(' ', $html);
    }
}
