<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Ip extends Component implements ComponentInterface
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
