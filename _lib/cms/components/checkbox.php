<?php

namespace cms;

class checkbox extends integer
{
    public $field_sql = 'TINYINT';

    public function field(string $field_name, $value = '', array $options = [])
    {
        ?>
        <input type="checkbox" name="<?= $field_name; ?>" value="1" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?php if ($value) { ?>checked<?php } ?> <?= $options['attribs']; ?>>
        <?php
    }

    public function value($value, $name = '')
    {
        $value = $value ? 'Yes' : 'No';
        return $value;
    }

    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = '')
    {
        return component::conditions_to_sql($field_name, $value, $func, $table_prefix);
    }

    public function search_field($name, $value)
    {
        $field_name = underscored($name); ?>
        <div>
            <label for="<?= underscored($name); ?>" class="col-form-label"><?= ucfirst($name); ?></label><br>
            <select name="<?= $field_name; ?>" class="form-control">
                <option value=""></option>
                <?= html_options([1 => 'Yes', 0 => 'No'], $_GET[$field_name]); ?>
            </select>
            <br>
            <br>
        </div>
        <?php
    }
}
