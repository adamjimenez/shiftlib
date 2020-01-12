<?php

namespace cms\components;

use cms\ComponentInterface;

class Timestamp extends Date implements ComponentInterface
{
    public $field_type = 'hidden';

    public function getFieldSql(): string
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

    public function format_value($value)
    {
        return false;
    }
}
