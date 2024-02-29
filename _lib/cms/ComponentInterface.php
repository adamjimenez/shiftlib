<?php

namespace cms;

interface ComponentInterface
{
    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string;

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = '');

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool;

    /**
     * @param string $fieldName
     * @param mixed $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string;

    /**
     * @return null|string
     */
    public function getFieldSql(): ?string;

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return mixed
     */
    public function formatValue($value, string $fieldName = null);
}
