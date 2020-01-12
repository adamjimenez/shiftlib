<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Time extends Component implements ComponentInterface
{
    public $field_sql = 'TIME';

    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="time" step="1" data-type="time" id="<?= $field_name; ?>" name="<?= $field_name; ?>" value="<?= ('00:00:00' != $value) ? substr($value, 0, -3) : ''; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?>/>
        <?php
    }
}
