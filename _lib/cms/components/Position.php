<?php

namespace cms\components;

use cms\ComponentInterface;

class Position extends Integer implements ComponentInterface
{
    public function formatValue($value)
    {
        global $cms;

        // add 1 to max position
        if (!$cms->id) {
            // todo position might have a different field name..
            $max_pos = sql_query('SELECT MAX(position) AS `max_pos` FROM `' . $cms->table . '`', 1);
            return $max_pos['max_pos'] + 1;
        }

        return $value ?: false;
    }
}