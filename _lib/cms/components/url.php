<?php

namespace cms;

class url extends component
{
    public function value($value, $name = '')
    {
        return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
    }

    public function is_valid($value): bool
    {
        return is_url($value);
    }
}
