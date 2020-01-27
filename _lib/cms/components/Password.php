<?php

namespace cms\components;

use auth;
use cms\Component;
use cms\ComponentInterface;

class Password extends Component implements ComponentInterface
{
    public $field_type = 'password';
    public $preserve_value = true;

    /**
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return '';
    }

    /**
     * @param $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        /** @var auth $auth */
        global $auth;

        // add 1 to max position
        if ($auth->hash_password) {
            $value = $auth->create_hash($value);
        }

        return $value ?: false;
    }
}
