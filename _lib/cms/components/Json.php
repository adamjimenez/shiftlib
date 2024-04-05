<?php

namespace cms\components;

use cms\ComponentInterface;

class Json extends Textarea implements ComponentInterface
{
    /**
     * Returns the display value
     *
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return trim($value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function is_valid($value): bool
    {
        return json_decode($value) !== null;
    }
}
