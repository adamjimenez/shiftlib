<?php

namespace cms;

class ip extends component
{
    public function value($value, $name = '')
    {
        global $cms;

        if (!$cms->id) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return false;
    }
}
