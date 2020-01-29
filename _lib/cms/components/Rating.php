<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Rating extends Component implements ComponentInterface
{
    public $rating_opts = [
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ];

    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TINYINT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', $options = []): string
    {
        $html = [];
        $html[] = '<select name="' . $fieldName . '" class="rating" ' . $options['attribs'] . '>';
        $html[] = '<option value="">Choose</option>';
        $html[] = html_options($this->rating_opts, $value, true);
        $html[] = '</select>';
        return implode(' ', $html);
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        $field_name = underscored($name);

        return '<select name="' . $field_name . '" class="rating" disabled="disabled">
            <option value="">Choose</option>
            ' . html_options($this->rating_opts, $value, true) . '
        </select>';
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        return '';
    }
}
