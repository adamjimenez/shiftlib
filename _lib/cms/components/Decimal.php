<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Decimal extends Integer implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'DECIMAL( 8,2 )';
    }

    public function value($value, $name = ''): string
    {
        if ($value <= 0) {
            $value = '';
        } else {
            $value = number_format($value, 2);
        }
        return $value;
    }

    public function formatValue($value)
    {
        return component::formatValue($value);
    }
}
