<?php

namespace cms;

class decimal extends integer
{
    public $field_sql = 'DECIMAL( 8,2 )';

    public function value($value, $name = '')
    {
        if ($value <= 0) {
            $value = '';
        } else {
            $value = number_format($value, 2);
        }
        return $value;
    }

    public function format_value($value)
    {
        return component::format_value($value);
    }
}
