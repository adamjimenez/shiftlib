<?php

namespace cms\components;

use cms;
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
        $parts[] = '<input type="hidden" name="' . $fieldName . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . ' value="' . $value . '">';
        $parts[] = '<input type="text" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . ' value="' . $this->cms->content[$fieldName . '_label'] . '" data-type="combo" data-field="' . $fieldName . '">';
        return implode(' ', $parts);
    }

    /**
     * TODO: This won't work currently ($this->cms->get_field)
     * @param string $name
     * @param $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $html = [];
        $html[] = '<div>';
        $html[] = $name;
        $html[] = '</div>';
        $html[] = $this->cms->get_field($name, 'class="form-control"');
        $html[] = '<br>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
