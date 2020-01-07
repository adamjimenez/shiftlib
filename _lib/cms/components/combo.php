<?php

namespace cms;

class combo extends select
{
    public $field_sql = "VARCHAR( 64 ) NOT NULL DEFAULT ''";

    public function field($field_name, $value = '', $options = [])
    {
        global $cms; ?>
        <input type="hidden" name="<?= $field_name; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?> value="<?= $value; ?>">
        <input type="text" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?> value="<?= $cms->content[$field_name . '_label']; ?>" data-type="combo" data-field="<?= $field_name; ?>">
        <?php
    }

    public function search_field($name, $value)
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
