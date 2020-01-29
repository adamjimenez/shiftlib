<?php

namespace cms;

abstract class Component
{
    /**
     * Used for input field
     *
     * @var string
     */
    public $fieldType = 'text';

    /**
     * Keep value when empty
     *
     * @var bool
     */
    public $preserveValue = false;

    /**
     * Whether we need an id to save the value
     *
     * @var bool
     */
    public $idRequired = false;

    /**
     * @var \cms
     */
    protected $cms;

    /**
     * @var \auth
     */
    protected $auth;

    /**
     * @var array
     */
    protected $vars;

    /**
     * Component constructor.
     * @param \cms $cms
     * @param \auth $auth
     * @param array $vars
     */
    public function __construct(\cms $cms, \auth $auth, array $vars)
    {
        $this->cms = $cms;
        $this->auth = $auth;
        $this->vars = $vars;
    }

    /**
     * SQL code for field creation
     *
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return "VARCHAR( 140 ) NOT NULL DEFAULT ''";
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="' . $this->fieldType . '" name="' . $fieldName . '" value="' . htmlspecialchars($value) . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . ($options['placeholder'] ? 'placeholder="' . $options['placeholder'] . ' "' : '') . ' ' . $options['attribs'] . '>';
    }

    /**
     * Returns the display value
     *
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
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
     * @param string|null $fieldName
     * @return string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return trim(strip_tags($value));
    }

    /**
     * @param string $fieldName
     * @param $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string
    {
        $value = str_replace('*', '%', $value);
        return $tablePrefix . $fieldName . " LIKE '" . escape($value) . "'";
    }

    /**
     * @param $name
     * @param $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);
        $html = [];
        $html[] = '<label for="' . $field_name . '" class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '<input type="text" class="form-control" name="' . $field_name . '" value="' . $value . '">';

        return implode(' ', $html);
    }
}
