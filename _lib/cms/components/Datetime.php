<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Datetime extends Date implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'DATETIME';
    }

    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="datetime-local" name="<?= $field_name; ?>" value="<?= $value; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?= $options['attribs'] ?: ''; ?>>
        <?php
    }

    public function value($value, $name = ''): string
    {
        if (starts_with($value, '0000-00-00')) {
            $value = '';
        }

        return $value ?: '';
    }

    public function formatValue($value)
    {
        if ($value) {
            $value .= ':00';
        }
        return $value;
    }

    public function isValid($value): bool
    {
        return component::isValid($value);
    }
}
