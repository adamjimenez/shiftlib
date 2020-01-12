<?php

namespace cms\components;

use cms\ComponentInterface;

class Timestamp extends Date implements ComponentInterface
{
    public $field_type = 'hidden';
    public $field_sql = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';

    /**
     * https://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
     *
     * @param mixed $value
     * @return bool
     */
    public function is_valid($value): bool
    {
        return ((string) (int) $value === $value) && ($value <= PHP_INT_MAX) && ($value >= ~PHP_INT_MAX);
    }

    public function format_value($value)
    {
        return false;
    }
}
