<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Coords extends Component implements ComponentInterface
{
    public function getFieldSql(): string
    {
        return "POINT";
    }

    function field($field_name, $value = '', $options = []): void
    {
        ?>
        <input type="text"
               name="<?= $field_name; ?>"
               value="<?= htmlspecialchars(substr($value, 6, -1)); ?>"
               <?php if ($options['readonly']) { ?>disabled<?php } ?>
               size="50"
               <?= $options['attribs']; ?>
               <?php if ($options['placeholder']) { ?>placeholder="<?= $options['placeholder']; ?>"<?php } ?>
        >
        <?
    }
}