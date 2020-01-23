<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class SelectParent extends Component implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return "INT";
    }

    public function field(string $field_name, $value = '', array $options = []): void
    {
        global $vars, $cms;

        $parent_field = array_search('parent', $vars['fields'][$cms->section]);

        reset($vars['fields'][$cms->section]);

        $label = key($vars['fields'][$cms->section]);

        $rows = sql_query("SELECT id,`$label` FROM `" . $cms->table . "` ORDER BY `$label`");

        $parents = [];
        foreach ($rows as $row) {
            if ($row['id'] == $cms->id) {
                continue;
            }
            $parents[$row['id']] = $row[$label];
        } ?>
        <select name="<?= $field_name; ?>" <?php if ($options['readonly']) { ?>readonly<?php } ?> <?= $options['attribs']; ?>>
            <option value=""></option>
            <?= html_options($parents, $value); ?>
        </select>
        <?php
    }

    public function value($value, $name = ''): string
    {
        global $vars, $cms;

        reset($vars['fields'][$cms->section]);

        $field = key($vars['fields'][$cms->section]);

        $row = sql_query("SELECT id,`$field` FROM `" . $cms->table . "` WHERE id='" . escape($value) . "' ORDER BY `$field`", 1);

        $value = '<a href="?option=' . escape($cms->section) . '&view=true&id=' . $value . '">' . ($row[$field]) . '</a>';

        return $value;
    }
}