<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Month extends Date implements ComponentInterface
{
    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="text" class="month" id="<?= $field_name; ?>" name="<?= $field_name; ?>" value="<?= ($value && '0000-00-00' != $value) ? $value : ''; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?= $options['attribs'] ?: 'style="width:75px;"'; ?>/>
        <?php
    }

    public function value($value, $name = ''): string
    {
        if ('0000-00-00' != $value and '' != $value) {
            $value = dateformat('F Y', $value);
        }
        return $value;
    }

    public function formatValue($value)
    {
        if ($value) {
            $value .= '-01';
        }
        return $value;
    }

    public function isValid($value): bool
    {
        return component::isValid($value);
    }
}
