<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Hidden extends Component implements ComponentInterface
{
    public $field_type = 'hidden';

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if ('0000-00-00 00:00:00' != $value) {
            $date = explode(' ', $value);
            $value = dateformat('d/m/Y', $date[0]) . ' ' . $date[1];
        }
        return $value;
    }
}
