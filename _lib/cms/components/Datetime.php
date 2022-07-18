<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Datetime extends Date implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'DATETIME';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="datetime-local" name="' . $fieldName . '" value="' . $value . '" ' . ($options['readonly'] ? 'disabled' : '') . ' size="10" ' . $options['attribs'] . '>';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if (true === starts_with($value, '0000-00-00')) {
            $value = '';
        }

        return $value ?: '';
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        if ($value) {
            $value .= ':00';
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return Component::isValid($value);
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
        if ('now' == $value) {
            $start = 'NOW()';
        } elseif ('month' == $func) {
            $start = dateformat('mY', $value);
        } else {
            $start = "'" . escape(dateformat('Y-m-d H:i:s', $value)) . "'";
        }

        if ($value and is_array($func) and $func['end']) {
            $end = escape($func['end']);

            $where = '(' . $tablePrefix . $fieldName . ' >= ' . $start . ' AND ' . $tablePrefix . $fieldName . " <= '" . $end . "')";
        } else {
            if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                $func = '=';
            }

            $where = $tablePrefix . $fieldName . ' ' . escape($func) . ' ' . $start;
        }

        return $where;
    }

    /**
     * @param $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);

        $html = [];
        $html[] = '<div>';
        $html[] = '<label class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '<div>';
        $html[] = '<div style="float:left">';
        $html[] = 'From&nbsp;';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '<input type="datetime-local" name="' . $field_name . '" value="' . $_GET[$field_name] . '" autocomplete="off" class="form-control">';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '&nbsp;To&nbsp;';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '<input type="datetime-local" name="func[' . $field_name . '][end]" value="' . $_GET['func'][$field_name]['end'] . '" autocomplete="off" class="form-control">';
        $html[] = '</div>';
        $html[] = '<br style="clear: both;">';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
