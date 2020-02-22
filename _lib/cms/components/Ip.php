<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Ip extends Component implements ComponentInterface
{
    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        if (!$this->cms->getId()) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return false;
    }
}
