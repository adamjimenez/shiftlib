<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Textarea extends Component implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return "TEXT";
    }

    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <textarea name="<?= $field_name; ?>"
                  <?php if ($options['readonly']) { ?>disabled<?php } ?>
                  <?php if ($options['placeholder']) { ?>placeholder="<?= $options['placeholder']; ?>"<?php } ?>
                  <?= $options['attribs']; ?>
        ><?= $value; ?></textarea>
        <?
    }
}