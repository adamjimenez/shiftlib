<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Password extends Component implements ComponentInterface
{
    public $fieldType = 'password';
    public $preserveValue = true;

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return '';
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        // add 1 to max position
        if ($this->auth->hash_password) {
            $value = $this->auth->create_hash($value);
        }

        return $value ?: false;
    }
}
