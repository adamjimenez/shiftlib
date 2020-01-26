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
     * @param $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string;

    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool;

    /**
     * @param string $fieldName
     * @param $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string;

    /**
     * @param $name
     * @param $value
     * @return string
     */
    public function searchField(string $name, $value): string;


    /**
     * @return string
     */
    public function getFieldSql(): ?string;

    /**
     * @param $value
     * @param string|null $fieldName
     * @return mixed
     */
    public function formatValue($value, string $fieldName = null);
}
