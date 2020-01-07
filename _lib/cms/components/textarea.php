<?php

namespace cms;

class textarea extends component
{
    public $field_sql = "TEXT";

    public function field(string $field_name, $value = '', $options = [])
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