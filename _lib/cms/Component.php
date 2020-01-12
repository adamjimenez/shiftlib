<?php

namespace cms;

abstract class Component
{
    /**
     * Used for input field
     *
     * @var string
     */
    public $field_type = 'text';

    /**
     * SQL code for field creation
     *
     * @var string
     */
    public $field_sql = "VARCHAR( 140 ) NOT NULL DEFAULT ''";

    /**
     * Keep value when empty
     *
     * @var bool
     */
    public $preserve_value = false;

    /**
     * Whether we need an id to save the value
     *
     * @var bool
     */
    public $id_required = false;

    /**
     * Returns the editable field
     *
     * @param string $field_name
     * @param string $value
     * @param array $options
     */
    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="<?= $this->field_type; ?>" name="<?= $field_name; ?>" value="<?= htmlspecialchars($value); ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?php if ($options['placeholder']) { ?>placeholder="<?= $options['placeholder']; ?>"<?php } ?> <?= $options['attribs']; ?>>
        <?php
    }

    // returns the display value
    public function value($value, $name = '')
    {
        return trim($value);
    }

    // checks the value is valid
    public function is_valid($value): bool
    {
        return true;
    }

    // applies any cleanup before saving value is mixed
    public function format_value($value, $field_name = null)
    {
        return trim(strip_tags($value));
    }

    // generates sql code for use in where statement
    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = ''): string
    {
        $value = str_replace('*', '%', $value);
        return $table_prefix . $field_name . " LIKE '" . escape($value) . "'";
    }

    public function search_field($name, $value): void
    {
        $field_name = underscored($name); ?>
        <label for="<?= $field_name; ?>" class="col-form-label"><?= ucfirst($name); ?></label>
        <input type="text" class="form-control" name="<?= $field_name; ?>" value="<?= $value; ?>">
        <?php
    }
}
