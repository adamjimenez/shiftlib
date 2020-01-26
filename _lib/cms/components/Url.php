<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Url extends Component implements ComponentInterface
{
    public function value($value, $name = ''): string
    {
        return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
    }

    public function isValid($value): bool
    {
        return preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $value);
    }
}
