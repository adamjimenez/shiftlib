<?php

namespace cms;

class integer extends component
{
    public $field_type = 'number';
    public $field_sql = 'INT';

    public function value($value)
    {
        $value = number_format($value);
        return $value;
    }

    public function is_valid($value)
    {
        return is_numeric($value);
    }

    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = '')
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
    public function format_value($value)
    {
        return (int) $value;
    }

    public function search_field($name, $value)
    {
        $field_name = underscored($name); ?>
        <label><?= ucfirst($name); ?></label><br>

        <div>
            <div style="float:left">
                <select name="func[<?= $field_name; ?>]">
                    <option value=""></option>
                    <?= html_options(['=' => '=', '!=' => '!=', '>' => '>', '<' => '<'], $_GET['func'][$field_name]); ?>
                </select>
            </div>
            <div style="float:left">
                <input type="number" id="<?= $name; ?>" name="<?= $field_name; ?>" value="<?= $_GET[$field_name]; ?>" size="8"/>
            </div>
            <br style="clear: both;">
        </div>
        <?php
    }
}
