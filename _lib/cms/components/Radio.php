<?php

namespace cms\components;

use cms\ComponentInterface;

/**
 * TODO: This won't work, missing properties etc
 *
 * Class radio
 * @package cms\components
 */
class Radio extends Select implements ComponentInterface
{
    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        global $vars;

        $name = spaced($fieldName);
        $vars['options'][$name] = $this->get_options($name, false);

        $assoc = is_assoc_array($vars['options'][$name]);
        $html = [];
        foreach ($vars['options'][$name] as $k => $v) {
            $val = $assoc ? $k : $v;
            $html[] = '<label ' . $options['attribs'] . '><input type="radio" name="' . $fieldName . '" value="' . $val . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . (isset($value) && $val == $value ? 'checked="checked"' : '') . ' ' . $options['attribs'] . '>' . $v . '&nbsp;</label>' . $options['separator'];
        }

        return implode(' ', $html);
    }

    /**
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        global $vars;

        if (false === is_array($vars['options'][$name])) {
            if ('0' == $value) {
                $value = '';
            } else {
                $value = '<a href="?option=' . escape($vars['options'][$name]) . '&view=true&id=' . $value . '">' . $this->content[underscored($name) . '_label'] . '</a>';
            }
        } else {
            if (is_assoc_array($vars['options'][$name])) {
                $value = $vars['options'][$name][$value];
            }
        }

        return $value ?: '';
    }
}
