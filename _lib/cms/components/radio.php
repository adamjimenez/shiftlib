<?php

namespace cms;

class radio extends select
{
    public function field(string $field_name, $value = '', array $options = [])
    {
        global $cms, $vars;

        $name = spaced($field_name);
        $vars['options'][$name] = $this->get_options($name, $where);

        $assoc = is_assoc_array($vars['options'][$name]);
        foreach ($vars['options'][$name] as $k => $v) {
            $val = $assoc ? $k : $v; ?>
        <label <?= $attribs; ?>><input type="radio" name="<?= $field_name; ?>" value="<?= $val; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?php if (isset($value) and $val == $value) { ?>checked="checked"<?php } ?> <?= $options['attribs']; ?>> <?= $v; ?> &nbsp;</label><?= $options['separator']; ?>
            <?php
        }
    }

    public function value($value, $name = '')
    {
        global $vars;

        if (!is_array($vars['options'][$name])) {
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

        return $value;
    }
}
