<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Upload extends Component implements ComponentInterface
{
    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="text" name="<?= $field_name; ?>" class="upload" value="<?= $value; ?>">
        <?php
    }

    public function value($value, $name = ''): string
    {
        if ($value) {
            $value = '
                <img src="/_lib/phpupload/?func=preview&file=' . $value . '&w=320&h=240" id="' . $name . '_thumb"><br>
                <label id="' . $name . '_label">' . $value . '</label>
			';
        }
        return $value;
    }
}
