<?php

namespace cms\components;

use cms\ComponentInterface;

class Uploads extends Upload implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TEXT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<textarea name="' . $fieldName . '" class="upload">' . $value . '</textarea>';
    }

    /**
     * @param $value
     * @param string $name
     * @return string
     */
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
