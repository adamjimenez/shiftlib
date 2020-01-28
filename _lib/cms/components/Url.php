<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Url extends Component implements ComponentInterface
{
    /**
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
    }

    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $value);
    }
}
