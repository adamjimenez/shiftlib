<?php

namespace cms\components;

use cms\ComponentInterface;

class Dob extends Date implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'DATE';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="date" placeholder="yyyy-mm-dd" id="' . $fieldName . '" name="' . $fieldName . '" value="' . ($value && '0000-00-00' != $value ? $value : '') . '" ' . ($options['readonly'] ? 'disabled' : '') . ' size="10" ' . $options['attribs'] . ' autocomplete="off">';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if ('0000-00-00' != $value and '' != $value) {
            $age = age($value);
            $value = dateformat('d/m/Y', $value);
        }

        return $value . ' (' . $age . ')';
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
        return '`' . $fieldName . "`!='0000-00-00' AND " .
            "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(" . $fieldName . ", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(" . $fieldName . ", '00-%m-%d')) LIKE '" . escape($value) . ' ';
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $fieldName = underscored($name);

        $opts = [];
        for ($i = 1; $i <= 60; $i++) {
            $opts['age'][] = $i;
        }

        $html = [];

        $html[] = ucfirst($name) . '<br>';
        $html[] = '<select name="' . $fieldName . '">';
        $html[] = '<option value="">Any</option>';
        $html[] = html_options($opts['age'], $_GET[$fieldName]);
        $html[] = '</select>';
        $html[] = 'to';
        $html[] = '<select name="func[' . $fieldName . ']">';
        $html[] = '<option value="">Any</option>';
        $html[] = html_options($opts['age'], $_GET['func'][$fieldName]);
        $html[] = '</select>';
        $html[] = '<br>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
