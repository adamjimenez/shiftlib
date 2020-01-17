<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Email extends Component implements ComponentInterface
{
    public $field_type = 'email';

    public function value($value, $name = ''): string
    {
        $value = '<a href="mailto:' . $value . '" target="_blank">' . $value . '</a>';
        return $value;
    }

    public function is_valid($value): bool
    {
        return is_email($value);
    }
}
