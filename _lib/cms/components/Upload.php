<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Upload extends Component implements ComponentInterface
{
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="text" name="' . $fieldName . '" class="upload" value="' . $value . '">';
    }

    public function value($value, string $name = ''): string
    {
        if ($value) {
            $value = '
                <img src="/_lib/phpupload/?func=preview&file=' . $value . '&w=320&h=240" id="' . $name . '_thumb"><br>
                <label id="' . $name . '_label">' . $value . '</label>
			';
        }
        return $value ?: '';
    }
}
