<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Polygon extends Component implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'POLYGON';
    }
    
    public function value($value, string $name = ''): string
    {
        return (string)$value;
    }

    /**
     * SQL code for column selection
     *
     * @return string|null
     */
    public function getColSql(string $fieldName, string $tablePrefix): ?string
    {
        $col = $tablePrefix . $fieldName;
        return "REPLACE(REPLACE(ST_ASText(" . $col . "), 'POLYGON((', ''), ')', '')";
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
        
        // remove last point from value
        $pos = strrpos($value, ',');
        $value = substr($value, 0, $pos - 1);
        
        return '<input type="text" name="' . $fieldName . '" value="' . htmlspecialchars($value) . '"' . ($options['readonly'] ? ' disabled' : '') . ' size="50"' . ($options['placeholder'] ? ' placeholder="' . $options['placeholder'] . '"' : '') . ' ' . $options['attribs'] . ' data-type="polygon" data-key="' . $public_maps_api_key . '">';
    }
}
