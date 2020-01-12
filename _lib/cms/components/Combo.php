<?php

namespace cms\components;

use cms\ComponentInterface;

class Combo extends Select implements ComponentInterface
{
    public $field_sql = "VARCHAR( 64 ) NOT NULL DEFAULT ''";

    public function field(string $field_name, $value = '', array $options = []): void
    {
        global $cms; ?>
        <input type="hidden" name="<?= $field_name; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?> value="<?= $value; ?>">
        <input type="text" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?> value="<?= $cms->content[$field_name . '_label']; ?>" data-type="combo" data-field="<?= $field_name; ?>">
        <?php
    }

    public function search_field($name, $value): void
    {
        global $cms;

        $field_name = underscored($name); ?>
        <div>
            <?= $name; ?>
        </div>
        <?= $cms->get_field($name, 'class="form-control"'); ?>
        <br>
        <br>
        <?php
    }
}
