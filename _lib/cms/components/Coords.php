<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Coords extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'POINT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', $options = []): string
    {
        return '<input type="text"
                       name="' . $fieldName . '"
                       value="' . htmlspecialchars(substr($value, 6, -1)) . '"
                       ' . ($options['readonly'] ? 'disabled' : '') . '
                       size="50"
                       ' . $options['attribs'] . '
                       ' . ($options['placeholder'] ? 'placeholder="' . $options['placeholder'] . '"' : '') . '>';
    }
}
