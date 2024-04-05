<?php

namespace cms\components;

use cms\ComponentInterface;

class Combo extends Select implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return "VARCHAR( 64 ) NOT NULL DEFAULT ''";
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        $parts = [];
        $parts[] = '<input type="hidden" name="' . $fieldName . '"' . ($options['readonly'] ? ' disabled' : '') . ' value="' . $value . '" ' . $options['attribs'] . '>';
        $parts[] = '<input type="text"' . ($options['readonly'] ? ' disabled' : '') . ' value="' . ($this->cms->content[$fieldName . '_label'] ?: $this->cms->content[$fieldName]) . '" data-type="combo" data-field="' . $fieldName . '" ' . $options['attribs'] . '>';
        return implode(' ', $parts);
    }
}
