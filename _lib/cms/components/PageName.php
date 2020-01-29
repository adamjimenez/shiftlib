<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class PageName extends Component implements ComponentInterface
{
    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return str_to_pagename($value);
    }
}
