<?php

namespace cms;

class tel extends component
{
    public $field_type = 'tel';

    public function is_valid($value)
    {
        return is_tel($value);
    }
}
