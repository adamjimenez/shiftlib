<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Email extends Component implements ComponentInterface
{
    public $field_type = 'email';

    /**
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return '<a href="mailto:' . $value . '" target="_blank">' . $value . '</a>';
    }

    /**
     * @param $value
     * @return bool
     */
    public function is_valid($value): bool
    {
        return is_email($value);
    }
}
