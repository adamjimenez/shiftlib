<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Integer extends Component implements ComponentInterface
{
    public $fieldType = 'number';

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
    public function value($value, string $name = '')
    {
        return (int)$value;
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
        if (is_array($value)) {
            $valueStr = '';
            foreach ($value as $v) {
                $valueStr .= (int) ($v) . ',';
            }
            $valueStr = substr($valueStr, 0, -1);

            $where = $tablePrefix . $fieldName . ' IN (' . escape($valueStr) . ')';
        } else {
            // check for range
            $pos = strrpos($value, '-');
            
            if ($pos > 0) {
                $min = substr($value, 0, $pos);
                $max = substr($value, $pos + 1);
    
                $where = '(' .
                    $tablePrefix . $fieldName . " >= '" . escape($min) . "' AND " .
                    $tablePrefix . $fieldName . " <= '" . escape($max) . "'
                )";
            } else {
        
                if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                    $func = '=';
                }
    
                $where = $tablePrefix . $fieldName . ' ' . $func . " '" . escape($value) . "'";
            }
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
}
