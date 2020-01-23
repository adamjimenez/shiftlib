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
     * @param string $name
     * @return string
     */
    public function value($value, $name = ''): string;

    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool;

    /**
     * @param $field_name
     * @param $value
     * @param string $func
     * @param string $table_prefix
     * @return string|null
     */
    public function conditionsToSql($field_name, $value, $func = '', $table_prefix = ''): ?string;

    /**
     * @param $name
     * @param $value
     */
    public function searchField($name, $value): void;


    /**
     * @return string
     */
    public function getFieldSql(): ?string;
}
