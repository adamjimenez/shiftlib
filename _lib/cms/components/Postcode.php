<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Postcode extends Component implements ComponentInterface
{
    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return false !== format_postcode($value);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return format_postcode($value);
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $fieldName = underscored($name);

        //distance options
        $opts['distance'] = [
            3 => '3 miles',
            10 => '10 miles',
            15 => '15 miles',
            20 => '20 miles',
            30 => '30 miles',
            40 => '40 miles',
            50 => '50 miles',
            75 => '75 miles',
            100 => '100 miles',
            150 => '150 miles',
            200 => '200 miles',
        ];

        $html = [];

        $html[] = 'Distance from ' . $name . '<br>';

        $html[] = 'Within';

        $html[] = '<select name="func[' . $fieldName . ']">';
        $html[] = '<option value=""></option>';
        $html[] = html_options($opts['distance'], $_GET['func'][$fieldName]);
        $html[] = '</select>';
        $html[] = 'of';
        $html[] = '<input type="text" name="' . $fieldName . '" value="' . $_GET[$fieldName] . '" size="7">';
        return implode(' ', $html);
    }
}
