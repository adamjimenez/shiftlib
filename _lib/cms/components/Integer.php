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
        $values = is_array($value) ? $value : [$value];
        
        $conditions = [];
        
        foreach ($values as $v) {
            if (preg_match('/^[<>=!]/', $v, $matches)) {
                $comparator = $matches[0];
            } else {
                $comparator = $func ?: '=';
            }
    
            if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                $comparator = '=';
            }
            
            $v = preg_replace('/[^0-9.]/', '', $v);

            $conditions[] = $tablePrefix . $fieldName . ' ' . $comparator . " '" . escape($v) . "'";
        }
        
        return '(' . implode(' AND ', $conditions) . ')';
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
