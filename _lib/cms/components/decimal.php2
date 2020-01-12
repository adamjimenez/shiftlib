<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Decimal extends Integer implements ComponentInterface
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
