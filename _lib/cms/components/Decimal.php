<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Decimal extends Integer implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'DECIMAL(8,2)';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return is_numeric($value) ? number_format((float)$value, 2) : '';
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return Component::formatValue((float)$value, $fieldName);
    }
}
