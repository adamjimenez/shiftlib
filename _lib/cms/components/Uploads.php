<?php

namespace cms\components;

use cms\ComponentInterface;

class Uploads extends Upload implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'TEXT';
    }

    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<textarea name="' . $fieldName . '" class="upload">' . $value . '</textarea>';
    }

    public function value($value, string $name = ''): string
    {
        if ($value) {
            $files = explode("\n", trim($value));
        }

        $value = '<ul id="' . $name . '_files">';
        $count = 0;

        if (is_array($files)) {
            foreach ($files as $key => $val) {
                $count++;

                $value .= '
	                <li id="item_' . $name . '_' . $count . '">
	                    <img src="/_lib/phpupload/?func=preview&file=' . $val . '" id="file_' . $name . '_' . $count . '_thumb"><br>
	                    <label id="file_' . $name . '_' . $count . '_label">' . $val . '</label>
	                </li>
	            ';
            }
        }

        $value .= '</ul>';

        return $value;
    }
}
