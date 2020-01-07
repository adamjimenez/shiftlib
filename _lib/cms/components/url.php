<?php

namespace cms;

class url extends component
{
    public function value($value, $name = '')
    {
        $value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        return $value;
    }

    public function is_valid($value): bool
    {
        return is_url($value);
    }
}
