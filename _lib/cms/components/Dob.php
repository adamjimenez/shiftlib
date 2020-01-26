<?php

namespace cms\components;

use cms\ComponentInterface;

class Dob extends Date implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'DATE';
    }

    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="text" data-type="dob" id="<?= $field_name; ?>" name="<?= $field_name; ?>" value="<?= ($value && '0000-00-00' != $value) ? $value : ''; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?= $options['attribs']; ?>>
        <?php
    }

    public function value($value, $name = ''): string
    {
        if ('0000-00-00' != $value and '' != $value) {
            $age = age($value);
            $value = dateformat('d/m/Y', $value);
        }

        $value = $value . ' (' . $age . ')';
        return $value;
    }

    public function conditionsToSql($field_name, $value, $func = '', $table_prefix = ''): ?string
    {
        return '`' . $field_name . "`!='0000-00-00' AND " .
            "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(" . $field_name . ", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(" . $field_name . ", '00-%m-%d')) LIKE '" . escape($value) . ' ';
    }

    public function searchField($name, $value): void
    {
        $field_name = underscored($name);

        for ($i = 1; $i <= 60; $i++) {
            $opts['age'][] = $i;
        } ?>

        <?= ucfirst($name); ?><br>
        <select name="<?= $field_name; ?>">
            <option value="">Any</option>
            <?= html_options($opts['age'], $_GET[$field_name]); ?>
        </select>
        to
        <select name="func[<?= $field_name; ?>]">
            <option value="">Any</option>
            <?= html_options($opts['age'], $_GET['func'][$field_name]); ?>
        </select>
        <br>
        <br>
        <?php
    }
}
