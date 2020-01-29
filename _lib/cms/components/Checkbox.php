<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Checkbox extends Integer implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TINYINT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="checkbox" name="' . $fieldName . '" value="1" ' . ($options['readonly'] ? 'disabled' : '') . '  ' . ($value ? 'checked' : '') . ' ' . $options['attribs'] . '>';
    }

    /**
     * Output the
     *
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return $value ? 'Yes' : 'No';
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
        return Component::conditionsToSql($fieldName, $value, $func, $tablePrefix);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $fieldName = underscored($name);

        $html = [];
        $html[] = '<div>';
        $html[] = '<label for="' . underscored($name) . '" class="col-form-label">' . ucfirst($name) . '</label><br>';
        $html[] = '<select name="' . $fieldName . '" class="form-control">';
        $html[] = '<option value=""></option>';
        $html[] = html_options([1 => 'Yes', 0 => 'No'], $_GET[$fieldName]);
        $html[] = '</select>';
        $html[] = '<br>';
        $html[] = '<br>';
        $html[] = '</div>';

        return implode(' ', $html);
    }
}
