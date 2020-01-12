<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class PageName extends Component implements ComponentInterface
{
    public function formatValue($value)
    {
        return str_to_pagename($value);
    }
}
