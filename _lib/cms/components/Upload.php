<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Upload extends Component implements ComponentInterface
{
    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="text" name="' . $fieldName . '" class="upload" value="' . $value . '">';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if ($value) {
            return '<img src="/_lib/phpupload/?func=preview&file=' . $value . '&w=320&h=240" id="' . $name . '_thumb"><br><label id="' . $name . '_label">' . $value . '</label>';
        }

        return '';
    }
}