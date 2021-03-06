<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class SelectParent extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'INT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @throws \Exception
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        reset($this->vars['fields'][$this->cms->section]);

        $label = key($this->vars['fields'][$this->cms->section]);

        $rows = sql_query("SELECT id,`$label` FROM `" . $this->cms->table . "` ORDER BY `$label`");

        $parents = [];
        foreach ($rows as $row) {
            if ($row['id'] == $this->cms->id) {
                continue;
            }
            $parents[$row['id']] = $row[$label];
        }

        $html = [];
        $html[] = '<select name="' . $fieldName . '" ' . ($options['readonly'] ? 'readonly' : '') . ' ' . $options['attribs'] . '>';
        $html[] = '<option value=""></option>';
        $html[] = html_options($parents, $value);
        $html[] = '</select>';
        return implode(' ', $html);
    }

    /**
     * @param mixed $value
     * @param string $name
     * @throws \Exception
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        reset($this->vars['fields'][$this->cms->section]);

        $field = key($this->vars['fields'][$this->cms->section]);

        $row = sql_query("SELECT id,`$field` FROM `" . $this->cms->table . "` WHERE id='" . escape($value) . "' ORDER BY `$field`", 1);

        return '<a href="?option=' . escape($this->cms->section) . '&view=true&id=' . $value . '">' . ($row[$field]) . '</a>';
    }
}
