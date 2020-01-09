<?php

namespace cms;

class month extends date
{
    public function field(string $field_name, $value = '', array $options = [])
    {
        ?>
        <input type="text" class="month" id="<?= $field_name; ?>" name="<?= $field_name; ?>" value="<?= ($value && '0000-00-00' != $value) ? $value : ''; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?= $options['attribs'] ?: 'style="width:75px;"'; ?>/>
        <?php
    }

    public function value($value, $name = '')
    {
        if ('0000-00-00' != $value and '' != $value) {
            $value = dateformat('F Y', $value);
        }
        return $value;
    }

    public function format_value($value)
    {
        if ($value) {
            $value .= '-01';
        }
        return $value;
    }

    public function is_valid($value): bool
    {
        return Base::is_valid($value);
    }
}
