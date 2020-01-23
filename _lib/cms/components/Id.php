<?php

namespace cms\components;

use cms\ComponentInterface;

class Id extends Integer implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return null;
    }

    public function formatValue($value)
    {
        return false;
    }
}
