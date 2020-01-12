<?php

namespace cms;

interface ComponentInterface
{
    /**
     * @param string $field_name
     * @param string $value
     * @param array $options
     */
    public function field(string $field_name, $value = '', array $options = []): void;

    /**
     * @param $value
     * @return bool
     */
    public function is_valid($value): bool;

    /**
     * @param $field_name
     * @param $value
     * @param string $func
     * @param string $table_prefix
     * @return string|null
     */
    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = ''): ?string;

    /**
     * @param $name
     * @param $value
     */
    public function search_field($name, $value): void;
}
