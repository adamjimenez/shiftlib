<?php

namespace cms;

class id extends integer
{
    public $field_sql = null;

    public function format_value($value)
    {
        return false;
    }
}
