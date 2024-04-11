<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Time extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TIME';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="time" step="1" data-type="time" id="' . $fieldName . '" name="' . $fieldName . '" value="' . ('00:00:00' != $value ? substr($value, 0, -3) : '') . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . '/>';
    }
}