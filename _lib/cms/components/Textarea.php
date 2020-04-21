<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Textarea extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
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
        $html[] = ($options['readonly'] ? ' disabled' : '');
        $html[] = ($options['placeholder'] ? ' placeholder="' . $options['placeholder'] . '"' : '');
        $html[] = $options['attribs'];
        $html[] = '>' . $value . '</textarea>';

        return implode(' ', $html);
    }

    /**
     * Returns the display value
     *
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        return nl2br($value);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return trim(strip_tags($value, '<iframe>'));
    }
}
