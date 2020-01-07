<?php

namespace cms;

class ip extends component
{
    public function value($value)
    {
        global $cms;

        if (!$cms->id) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return false;
    }
}
