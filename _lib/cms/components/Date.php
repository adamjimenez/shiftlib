<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Date extends Component implements ComponentInterface
{
    public $dateFormat = 'Y-m-d';
    
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
        return '<input type="date" name="' . $fieldName . '" value="' . ($value && '0000-00-00' != $value ? $value : '') . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . '>';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if (starts_with($value, '0000-00-00')) {
            $value = '';
        } elseif ('' != $value) {
            $value = dateformat('d/m/Y', $value);
        }
        return $value ?: '';
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $value);
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
            $start = "'" . escape(dateformat($this->dateFormat, $value)) . "'";
        }

        if ('month' === $func) {
            $where = 'date_format(' . $tablePrefix . $fieldName . ", '%m%Y') = '" . escape(dateformat('mY', $value)) . "'";
        } elseif ('year' === $func) {
            $where = 'date_format(' . $tablePrefix . $fieldName . ", '%Y') = '" . escape($value) . "'";
        } elseif ($value && strtotime($func)) {
            $end = escape(dateformat($this->dateFormat, $func));
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
        $html[] = '<input type="text" name="' . $field_name . '" value="' . $_GET[$field_name] . '" size="8" data-type="date" autocomplete="off" class="form-control">';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '&nbsp;To&nbsp;';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '<input type="text" name="func[' . $field_name . '][end]" value="' . (is_array($_GET['func'][$field_name]) ? $_GET['func'][$field_name]['end'] : '') . '" size="8" data-type="date" autocomplete="off" class="form-control">';
        $html[] = '</div>';
        $html[] = '<br style="clear: both;">';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
