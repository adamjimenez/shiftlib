<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Rating extends Component implements ComponentInterface
{
    // rating widget options
    public $rating_opts = [
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ];

    public function getFieldSql(): ?string
    {
        return 'TINYINT';
    }

    public function field(string $fieldName, $value = '', $options = []): string
    {
        $html = [];
        $html[] = '<select name="' . $fieldName . '" class="rating" ' . $options['attribs'] . '>';
        $html[] = '<option value="">Choose</option>';
        $html[] = html_options($this->rating_opts, $value, true);
        $html[] = '</select>';
        return implode(' ', $html);
    }

    public function value($value, string $name = ''): string
    {
        $field_name = underscored($name);

        return '<select name="' . $field_name . '" class="rating" disabled="disabled">
            <option value="">Choose</option>
            ' . html_options($this->rating_opts, $value, true) . '
        </select>';
    }

    public function searchField(string $name, $value): string
    {
        return '';
    }
}
