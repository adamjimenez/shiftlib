<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Tel extends Component implements ComponentInterface
{
    public $field_type = 'tel';

    public function isValid($value): bool
    {
        return is_tel($value);
    }
}
