<?php

namespace cms\components;

use cms\ComponentInterface;

class Timestamp extends Date implements ComponentInterface
{
    public $fieldType = 'hidden';

    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
    }

    /**
     * https://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return ((string) (int) $value === $value) && ($value <= PHP_INT_MAX) && ($value >= ~PHP_INT_MAX);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return false;
    }
}
