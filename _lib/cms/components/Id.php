<?php

namespace cms\components;

use cms\ComponentInterface;

class Id extends Integer implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return null;
    }

    /**
     * @param $value
     * @param string|null $fieldName
     * @return bool|int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return false;
    }
}
