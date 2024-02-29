<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Postcode extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return "VARCHAR( 8 ) NOT NULL DEFAULT ''";
    }
    
    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return false !== format_postcode($value);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return format_postcode($value);
    }
}
