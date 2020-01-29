<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Integer extends Component implements ComponentInterface
{
    public $field_type = 'number';

    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'INT';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return number_format($value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return is_numeric($value);
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
        // check for range
        $pos = strrpos($value, '-');

        if ($pos > 0) {
            $min = substr($value, 0, $pos);
            $max = substr($value, $pos + 1);

            $where = '(' .
                $tablePrefix . $fieldName . " >= '" . escape($min) . "' AND " .
                $tablePrefix . $fieldName . " <= '" . escape($max) . "'
            )";
        } elseif (is_array($value)) {
            $value_str = '';
            foreach ($value as $v) {
                $value_str .= (int) ($v) . ',';
            }
            $value_str = substr($value_str, 0, -1);

            $where = $tablePrefix . $fieldName . ' IN (' . escape($value_str) . ')';
        } else {
            if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                $func = '=';
            }

            $where = $tablePrefix . $fieldName . ' ' . $func . " '" . escape($value) . "'";
        }

        return $where;
    }

    /**
     * Applies any cleanup before saving
     *
     * @param mixed $value
     * @param string|null $fieldName
     * @return int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return (int) $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);

        $html = [];
        $html[] = '<label>' . ucfirst($name) . '</label><br>';

        $html[] = '<div>';
        $html[] = '<div style="float:left">';
        $html[] = '<select name="func[' . $field_name . ']" class="form-control">';
        $html[] = '<option value=""></option>';
        $html[] = html_options(['=' => '=', '!=' => '!=', '>' => '>', '<' => '<'], $_GET['func'][$field_name]);
        $html[] = '</select>';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '<input type="number" id="' . $name . '" name="' . $field_name . '" value="' . $_GET[$field_name] . '" size="8" class="form-control">';
        $html[] = '</div>';
        $html[] = '<br style="clear: both;">';
        $html[] = '</div>';
        return implode(' ', $html);
    }
}
