<?php

namespace cms;

class url extends component
{
    public function value($value)
    {
        $value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        return $value;
    }

    public function is_valid($value)
    {
        return is_url($value);
    }
}
