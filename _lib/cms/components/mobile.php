<?php

namespace cms;

class mobile extends component
{
    public function is_valid($value)
    {
        return false !== format_mobile($value);
    }

    public function format_value($value)
    {
        return format_mobile($value);
    }
}
