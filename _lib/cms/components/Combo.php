<?php

namespace cms\components;

use cms\ComponentInterface;

class Combo extends Select implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return "VARCHAR( 64 ) NOT NULL DEFAULT ''";
    }

    public function field(string $fieldName, $value = '', array $options = []): string
    {
        global $cms;

        $parts = [];
        $parts[] = '<input type="hidden" name="' . $fieldName . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . ' value="' . $value . '">';
        $parts[] = '<input type="text" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . ' value="' . $cms->content[$fieldName . '_label'] . '" data-type="combo" data-field="' . $fieldName . '">';
        return implode(' ', $parts);
    }

    public function searchField(string $name, $value): string
    {
        global $cms;

        $html = [];
        $html[] = '<div>';
        $html[] = $name;
        $html[] = '</div>';
        $html[] = $cms->get_field($name, 'class="form-control"');
        $html[] = '<br>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
