<?php

namespace cms;

class datetime extends date
{
    public $field_sql = 'DATETIME';

    public function field($field_name, $value = '', $options = [])
    {
        ?>
        <input type="datetime-local" name="<?= $field_name; ?>" value="<?= $value; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?= $options['attribs'] ?: ''; ?>>
        <?php
    }

    public function value($value)
    {
        if (starts_with($value, '0000-00-00')) {
            $value = '';
        }

        return $value;
    }

    public function format_value($value)
    {
        if ($value) {
            $value .= ':00';
        }
        return $value;
    }

    public function is_valid($value)
    {
        return component::is_valid($value);
    }
}
