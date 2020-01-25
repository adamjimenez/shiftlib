<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Textarea extends Component implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'TEXT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        $html = [];
        $html[] = '<textarea name="' . $fieldName . '"';
        $html[] = ($options['readonly'] ? 'disabled' : '');
        $html[] = ($options['placeholder'] ? 'placeholder="' . $options['placeholder'] . '"' : '');
        $html[] = $options['attribs'];
        $html[] = '>' . $value . '</textarea>';

        return implode(' ', $html);
    }
}
