<?php

namespace cms;

class dob extends date
{
    public function value($value)
    {
        if ('0000-00-00' != $value and '' != $value) {
            $age = age($value);
            $value = dateformat('d/m/Y', $value);
        }

        $value = $value . ' (' . $age . ')';
        return $value;
    }

    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = '')
    {
        return '`' . $field_name . "`!='0000-00-00' AND " .
            "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(" . $field_name . ", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(" . $field_name . ", '00-%m-%d')) LIKE '" . escape($value) . ' ';
    }

    public function search_field($name, $value)
    {
        $field_name = underscored($name);

        for ($i = 1; $i <= 60; $i++) {
            $opts['age'][] = $i;
        } ?>

        <?= $name; ?><br>
        <select name="<?= $field_name; ?>">
            <option value="">Any</option>
            <?= html_options($opts['age'], $_GET[$field_name]); ?>
        </select>
        To
        <select name="func[<?= $field_name; ?>]">
            <option value="">Any</option>
            <?= html_options($opts['age'], $_GET['func'][$field_name]); ?>
        </select>
        <br>
        <?php
    }
}
