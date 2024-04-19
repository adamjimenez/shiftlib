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
        return preg_match('/^[0-9]{4}-(0[0-9]|1[0-2])-(0[0-9]|[1-2][0-9]|3[0-1])$/', $value);
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
            $end = end($value);
            $start = reset($value);
        } else {
            switch ($value) {
                case 'today':
                    $start = strtotime('today');
                    $end = $start;
                break;
                case 'yesterday':
                    $start = strtotime('yesterday');
                    $end = $start;
                break;
                case 'thismonth':
                    $start = strtotime('first day of this month');
                    $end = strtotime('last day of this month');
                break;
                case 'lastmonth':
                    $start = strtotime('first day of last month');
                    $end = strtotime('last day of last month');
                break;
                case 'thisyear':
                    $start = strtotime('first day of january this year');
                    $end = strtotime('last day of december this year');
                break;
                case 'lastyear':
                    $start = strtotime('first day of january last year');
                    $end = strtotime('last day of december last year');
                break;
                default:
                    preg_match("/^last([0-9]+)d$/", $value, $matches);
                    
                    if ($matches[1]) {
                        $start = strtotime('-' . ((int)$matches[1] - 1) . ' days');
                        $end = strtotime('today');
                    } else {
                        // backcompat
                        if (strtotime($func)) {
                            $end = strtotime($func);
                        }
                    }
                break;
            }
        }
        
        if ($end) {
            return '(' . $tablePrefix . $fieldName . " >= '" . dateformat('Y-m-d', $start) . "' AND " . $tablePrefix . $fieldName . " < '" . dateformat('Y-m-d', strtotime('tomorrow', make_timestamp($end))) . "')";
        }
        
        // backcompat
        if ('month' === $func) {
            $where = 'date_format(' . $tablePrefix . $fieldName . ", '%m%Y') = '" . escape(dateformat('mY', $value)) . "'";
        } elseif ('year' === $func) {
            $where = 'date_format(' . $tablePrefix . $fieldName . ", '%Y') = '" . escape($value) . "'";
        } else {
            if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                $func = '=';
            }
            
            if ('now' == $value) {
                $start = 'NOW()';
            } elseif ('month' == $func) {
                $start = dateformat('mY', $value);
            } else {
                $start = "'" . escape(dateformat($this->dateFormat, $value)) . "'";
            }

            $where = $tablePrefix . $fieldName . ' ' . escape($func) . ' ' . $start;
        }
        
        return $where;
    }
}
