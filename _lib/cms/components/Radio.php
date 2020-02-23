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
     * @throws \Exception
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        $name = spaced($fieldName);
        $this->vars['options'][$name] = $this->get_options($name, false);

        $assoc = is_assoc_array($this->vars['options'][$name]);
        $html = [];
        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $assoc ? $k : $v;
            $html[] = '<label ' . $options['attribs'] . '><input type="radio" name="' . $fieldName . '" value="' . $val . '"' . ($options['readonly'] ? ' disabled' : '') . (isset($value) && $val == $value ? ' checked="checked"' : '') . ' ' . $options['attribs'] . '>' . $v . '&nbsp;</label>' . $options['separator'];
        }

        return implode(' ', $html);
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if (false === is_array($this->vars['options'][$name])) {
            if ('0' == $value) {
                return '';
            }

            return '<a href="?option=' . escape($this->vars['options'][$name]) . '&view=true&id=' . $value . '">' . $this->content[underscored($name) . '_label'] . '</a>';
        }

        if (is_assoc_array($this->vars['options'][$name])) {
            return $this->vars['options'][$name][$value];
        }

        return '';
    }
}
