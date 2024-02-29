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
        return 'TINYINT(1)';
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return $value && $value !== 'false' ? 1 : 0;
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="checkbox" name="' . $fieldName . '" value="1"' . ($options['readonly'] ? ' disabled' : '') . ($value ? ' checked' : '') . ' ' . $options['attribs'] . '>';
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
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return is_numeric($value) or is_bool($value) or in_array($value, ['true', 'false']);
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
        $value = $value && $value !== 'false' ? 1 : 0;
        return Component::conditionsToSql($fieldName, $value, $func, $tablePrefix);
    }
}
