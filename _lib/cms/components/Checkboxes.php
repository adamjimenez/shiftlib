<?php

namespace cms\components;

use cms\ComponentInterface;

class Checkboxes extends Select implements ComponentInterface
{
    public $id_required = true;

    public function getFieldSql(): ?string
    {
        return null;
    }

    public function field(string $field_name, $value = '', array $options = []): void
    {
        global $vars, $cms;

        $name = spaced($field_name);

        $value = [];

        if (!is_array($vars['options'][$name]) and $vars['options'][$name]) {
            if ($cms->id) {
                $join_id = $cms->get_id_field($name);

                $rows = sql_query('SELECT T1.value FROM cms_multiple_select T1
                    INNER JOIN `' . escape(underscored($vars['options'][$name])) . "` T2 ON T1.value=T2.$join_id
                    WHERE
                        section='" . escape($cms->section) . "' AND
                        field='" . escape($name) . "' AND
                        item='" . $cms->id . "'
                ");

                foreach ($rows as $row) {
                    $value[] = $row['value'];
                }
            }

            if (in_array('language', $vars['fields'][$vars['options'][$name]])) {
                $language = $cms->language ? $cms->language : 'en';
                $table = underscored($vars['options'][$name]);

                foreach ($vars['fields'][$vars['options'][$name]] as $k => $v) {
                    if ('separator' != $v) {
                        $field = $k;
                        break;
                    }
                }

                $raw_option = $vars['fields'][$vars['options'][$name]][$field];

                $cols = '';
                $cols .= '`' . underscored($field) . '`';

                $rows = sql_query("SELECT id,$cols FROM
                    $table
                    WHERE
                        language='" . $language . "'
                    ORDER BY `" . underscored($field) . '`
                ');

                $options = [];
                foreach ($rows as $row) {
                    if ($row['translated_from']) {
                        $id = $row['translated_from'];
                    } else {
                        $id = $row['id'];
                    }

                    $options[$id] = $row[underscored($field)];
                }

                $vars['options'][$name] = $options;
            } else {
                //make sure we get the first field
                reset($vars['fields'][$vars['options'][$name]]);

                $vars['options'][$name] = $this->get_options($name, false);
            }
        } else {
            if ($cms->id) {
                $rows = sql_query("SELECT value FROM cms_multiple_select
                    WHERE
                        section='" . escape($cms->section) . "' AND
                        field='" . escape($name) . "' AND
                        item='" . $cms->id . "'
                ");

                foreach ($rows as $row) {
                    $value[] = $row['value'];
                }
            }
        }

        $is_assoc = is_assoc_array($vars['options'][$name]);

        print '<ul class="checkboxes">';

        foreach ($vars['options'][$name] as $k => $v) {
            $val = $is_assoc ? $k : $v; ?>
            <li><label><input type="checkbox" name="<?= $field_name; ?>[]" value="<?= $val; ?>" <?php if ($options['readonly']) { ?>readonly<?php } ?> <?php if (in_array($val, $value)) { ?>checked="checked"<?php } ?> /> <?= $v; ?></label></li>
            <?php
        }

        print '</ul>';
    }

    public function value($value, $name = '')
    {
        global $vars, $cms;

        $array = [];
        if (!is_array($vars['options'][$name]) and $vars['options'][$name]) {
            $join_id = $cms->get_id_field($name);

            //make sure we get the label from the first array item
            reset($vars['fields'][$vars['options'][$name]]);

            $rows = sql_query('SELECT `' . underscored(key($vars['fields'][$vars['options'][$name]])) . '`,T1.value FROM cms_multiple_select T1
                INNER JOIN `' . escape(underscored($vars['options'][$name])) . "` T2 ON T1.value = T2.$join_id
                WHERE
                    T1.field='" . escape($name) . "' AND
                    T1.item='" . $cms->id . "' AND
                    T1.section='" . $cms->section . "'
                GROUP BY T1.value
                ORDER BY T2." . underscored(key($vars['fields'][$vars['options'][$name]])) . '
            ');

            foreach ($rows as $row) {
                $array[] = '<a href="?option=' . escape($vars['options'][$name]) . '&view=true&id=' . $row['value'] . '">' . current($row) . '</a>';
            }
        } else {
            $rows = sql_query("SELECT value FROM cms_multiple_select
                WHERE
                    field='" . escape($name) . "' AND
                    item='" . $cms->id . "'
                ORDER BY id
            ");

            $is_assoc = is_assoc_array($vars['options'][$name]);
            foreach ($rows as $row) {
                $array[] = $is_assoc ? $vars['options'][$name][$row['value']] : current($row);
            }
        }

        $value = implode('<br>' . "\n", $array);
        return $value;
    }

    public function formatValue($value, $field_name = null)
    {
        global $cms;

        $name = spaced($field_name);

        if ($cms->id) {
            // create NOT IN string
            $value_str = '';
            if (count($value)) {
                foreach ($value as $v) {
                    $value_str .= "'" . escape($v) . "',";
                }
                $value_str = substr($value_str, 0, -1);
                $value_str = 'AND item NOT IN (' . $value_str . ')';
            }

            sql_query("DELETE FROM cms_multiple_select
                WHERE
                    section='" . escape($cms->section) . "' AND
                    field='" . escape($name) . "' AND
                    item='" . escape($cms->id) . "'
                    $value_str
            ");

            foreach ($value as $v) {
                sql_query("INSERT INTO cms_multiple_select SET
                    section='" . escape($cms->section) . "',
                    field='" . escape($name) . "',
                    item='" . escape($cms->id) . "',
                    value='" . escape($v) . "'
                ");
            }
        }

        return false;
    }

    // generates sql code for use in where statement
    public function conditionsToSql($field_name, $value, $func = '', $table_prefix = ''): ?string
    {
        return null;
    }

    public function searchField($name, $value): void
    {
        global $vars;

        $field_name = underscored($name);

        if (!is_array($vars['options'][$name]) and $vars['options'][$name]) {
            $vars['options'][$name] = $this->get_options(underscored($vars['options'][$name]), underscored(key($vars['fields'][$vars['options'][$name]])));
        } ?>
        <div>
            <label for="<?= underscored($name); ?>" class="col-form-label"><?= ucfirst($name); ?></label>
        </div>
        <div style="max-height: 200px; width: 200px; overflow: scroll">
            <?php
            $is_assoc = is_assoc_array($vars['options'][$name]);
        foreach ($vars['options'][$name] as $k => $v) {
            $val = $is_assoc ? $k : $v; ?>
                <label><input type="checkbox" name="<?= $field_name; ?>[]" value="<?= $val; ?>" <?php if (in_array($val, $_GET[$field_name])) { ?>checked<?php } ?>> <?= $v; ?></label><br>
                <?php
        } ?>
        </div>
        <?php
    }
}
