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
     * SQL code for field creation
     *
     * @var string
     */
    public function getFieldSql(): string
    {
        return "VARCHAR( 140 ) NOT NULL DEFAULT ''";
    }

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

    /**
     * Returns the display value
     *
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, $name = '')
    {
        return trim($value);
    }

    /**
     * Checks the value is valid
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return true;
    }

    /**
     * Applies any cleanup before saving value is mixed
     *
     * @param $value
     * @param null $field_name
     * @return string
     */
    public function format_value($value, $field_name = null)
    {
        return trim(strip_tags($value));
    }

    /**
     * @param $field_name
     * @param $value
     * @param string $func
     * @param string $table_prefix
     * @return string|null
     */
    public function conditionsToSql($field_name, $value, $func = '', $table_prefix = ''): ?string
    {
        $value = str_replace('*', '%', $value);
        return $table_prefix . $field_name . " LIKE '" . escape($value) . "'";
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function searchField($name, $value): void
    {
        $field_name = underscored($name); ?>
        <label for="<?= $field_name; ?>" class="col-form-label"><?= ucfirst($name); ?></label>
        <input type="text" class="form-control" name="<?= $field_name; ?>" value="<?= $value; ?>">
        <?php
    }
}
