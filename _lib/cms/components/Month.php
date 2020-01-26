<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Month extends Date implements ComponentInterface
{
    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="text" class="month" id="' . $fieldName . '" name="' . $fieldName . '" value="' . ($value && '0000-00-00' != $value ? $value : '') . '" ' . ($options['readonly'] ? 'disabled' : '') . ' size="10" ' . $options['attribs'] . ' style="width:75px;" />';
    }

    public function value($value, string $name = ''): string
    {
        if ('0000-00-00' != $value and '' != $value) {
            $value = dateformat('F Y', $value);
        }
        return $value;
    }

    public function formatValue($value, string $fieldName = null)
    {
        if ($value) {
            $value .= '-01';
        }
        return $value;
    }

    public function isValid($value): bool
    {
        return Component::isValid($value);
    }
}
