<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Datetime extends Date implements ComponentInterface
{
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

    public function value($value, string $name = ''): string
    {
        if (starts_with($value, '0000-00-00')) {
            $value = '';
        }

        return $value;
    }

    public function formatValue($value, string $fieldName = null)
    {
        if ($value) {
            $value .= ':00';
        }
        return $value;
    }

    public function isValid($value): bool
    {
        return Component::isValid($value);
    }
}
