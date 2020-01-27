<?php

namespace cms\components;

use cms\ComponentInterface;

class Dob extends Date implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'DATE';
    }

    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="text" data-type="dob" id="' . $fieldName . '" name="' . $fieldName . '" value="' . ($value && '0000-00-00' != $value ? $value : '') . ' ' . ($options['readonly'] ? 'disabled' : '') . ' size="10" ' . $options['attribs'] . '>';
    }

    public function value($value, string $name = ''): string
    {
        if ('0000-00-00' != $value and '' != $value) {
            $age = age($value);
            $value = dateformat('d/m/Y', $value);
        }

        return $value . ' (' . $age . ')';
    }

    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string
    {
        return '`' . $fieldName . "`!='0000-00-00' AND " .
            "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(" . $fieldName . ", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(" . $fieldName . ", '00-%m-%d')) LIKE '" . escape($value) . ' ';
    }

    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);

        for ($i = 1; $i <= 60; $i++) {
            $opts['age'][] = $i;
        }

        $html = [];

        $html[] = ucfirst($name) . '<br>';
        $html[] = '<select name="' . $field_name . '">';
        $html[] = '<option value="">Any</option>';
        $html[] = html_options($opts['age'], $_GET[$field_name]);
        $html[] = '</select>';
        $html[] = 'to';
        $html[] = '<select name="func[' . $field_name . ']">';
        $html[] = '<option value="">Any</option>';
        $html[] = html_options($opts['age'], $_GET['func'][$field_name]);
        $html[] = '</select>';
        $html[] = '<br>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
