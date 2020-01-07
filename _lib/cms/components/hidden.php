<?php

namespace cms;

class hidden extends component
{
    public $field_type = 'hidden';

    public function value($value)
    {
        if ('0000-00-00 00:00:00' != $value) {
            $date = explode(' ', $value);
            $value = dateformat('d/m/Y', $date[0]) . ' ' . $date[1];
        }
        return $value;
    }
}
