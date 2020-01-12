<?php

namespace cms;

interface ComponentInterface
{
    public function field(string $field_name, $value = '', array $options = []): void;

    public function is_valid($value): bool;

    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = ''): string;

    public function search_field($name, $value): void;
}
