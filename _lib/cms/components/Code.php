<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Code extends Textarea implements ComponentInterface
{
    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return $value;
    }
}
