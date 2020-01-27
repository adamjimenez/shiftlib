<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Tel extends Component implements ComponentInterface
{
    public $field_type = 'tel';

    public function isValid($value): bool
    {
        return preg_match("/^[0-9\-\s]+$/", $value);
    }
}
