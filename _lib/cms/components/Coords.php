<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Coords extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'POINT';
    }

    /**
     * SQL code for column selection
     *
     * @return string|null
     */
    public function getColSql(string $fieldName, string $tablePrefix): ?string
    {
        $col = $tablePrefix . $fieldName;
        return 'CONCAT(X(' . $col . '), ", ", Y(' . $col . '))';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', $options = []): string
    {
        global $public_maps_api_key;
        
        return '<input type="text" name="' . $fieldName . '" value="' . htmlspecialchars($value) . '"' . ($options['readonly'] ? ' disabled' : '') . ' size="50"' . ($options['placeholder'] ? ' placeholder="' . $options['placeholder'] . '"' : '') . ' ' . $options['attribs'] . ' data-type="coords" data-key="' . $public_maps_api_key . '">';
    }
}
