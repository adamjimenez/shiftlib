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
        return 'INT UNSIGNED NOT NULL AUTO_INCREMENT';
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return false;
    }
}
