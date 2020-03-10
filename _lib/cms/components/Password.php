<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Password extends Component implements ComponentInterface
{
    public $fieldType = 'password';
    public $preserveValue = true;

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="' . $this->fieldType . '" name="' . $fieldName . '" value=""' . ($options['readonly'] ? ' disabled' : '') . ($options['placeholder'] ? ' placeholder="' . $options['placeholder'] . '"' : '') . ' ' . $options['attribs'] . '>';
    }

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
        if ($value && $this->auth->shouldHashPassword()) {
            $value = $this->auth->create_hash($value);
        }

        return $value ?: false;
    }
}
