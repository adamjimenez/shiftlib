<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Url extends Component implements ComponentInterface
{
    public function value($value, $name = '')
    {
        return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
    }

    public function isValid($value): bool
    {
        return is_url($value);
    }
}
