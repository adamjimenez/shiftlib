<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Tel extends Component implements ComponentInterface
{
    public $fieldType = 'tel';
    
    public function value($value, string $name = ''): string
    {
        return '<a href="tel:' . $value . '">' . $value . '</a>';
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return false !== format_tel($value);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string|string[]|null
     */
    public function formatValue($value, string $fieldName = null)
    {
        return format_tel($value);
    }
}
