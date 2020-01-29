<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Mobile extends Component implements ComponentInterface
{
    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return false !== format_mobile($value);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string|string[]|null
     */
    public function formatValue($value, string $fieldName = null)
    {
        return format_mobile($value);
    }
}
