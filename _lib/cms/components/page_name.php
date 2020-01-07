<?php

namespace cms;

class page_name extends component
{
    public function format_value($value)
    {
        return str_to_pagename($value);
    }
}
