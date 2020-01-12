<?php

namespace cms\components;

use cms\ComponentInterface;

class Id extends Integer implements ComponentInterface
{
    public $field_sql = null;

    public function format_value($value)
    {
        return false;
    }
}
