<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Integer extends Component implements ComponentInterface
{
    public $field_type = 'number';

    public function getFieldSql(): ?string
    {
        return 'INT';
    }

    public function value($value, $name = '')
    {
        $value = number_format($value);
        return $value;
    }

    public function isValid($value): bool
    {
        return is_numeric($value);
    }

    public function conditionsToSql($field_name, $value, $func = '', $table_prefix = ''): ?string
    {
        // check for range
        $pos = strrpos($value, '-');

        if ($pos > 0) {
            $min = substr($value, 0, $pos);
            $max = substr($value, $pos + 1);

            $where = '(' .
                $table_prefix . $field_name . " >= '" . escape($min) . "' AND " .
                $table_prefix . $field_name . " <= '" . escape($max) . "'
            )";
        } elseif (is_array($value)) {
            $value_str = '';
            foreach ($value as $v) {
                $value_str .= (int) ($v) . ',';
            }
            $value_str = substr($value_str, 0, -1);

            $where = $table_prefix . $field_name . ' IN (' . escape($value_str) . ')';
        } else {
            if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                $func = '=';
            }

            $where = $table_prefix . $field_name . ' ' . $func . " '" . escape($value) . "'";
        }

        return $where;
    }

    // applies any cleanup before saving
    public function formatValue($value)
    {
        return (int) $value;
    }

    public function searchField($name, $value): void
    {
        $field_name = underscored($name); ?>
        <label><?= ucfirst($name); ?></label><br>

        <div>
            <div style="float:left">
                <select name="func[<?= $field_name; ?>]" class="form-control">
                    <option value=""></option>
                    <?= html_options(['=' => '=', '!=' => '!=', '>' => '>', '<' => '<'], $_GET['func'][$field_name]); ?>
                </select>
            </div>
            <div style="float:left">
                <input type="number" id="<?= $name; ?>" name="<?= $field_name; ?>" value="<?= $_GET[$field_name]; ?>" size="8" class="form-control">
            </div>
            <br style="clear: both;">
        </div>
        <?php
    }
}
