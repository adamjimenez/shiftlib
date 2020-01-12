<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Mobile extends Component implements ComponentInterface
{
    public function isValid($value): bool
    {
        return false !== format_mobile($value);
    }

    public function format_value($value)
    {
        return format_mobile($value);
    }
}
