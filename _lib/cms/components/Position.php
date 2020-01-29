<?php

namespace cms\components;

use cms\ComponentInterface;

class Position extends Integer implements ComponentInterface
{
    /**
     * @param $value
     * @param string|null $fieldName
     * @throws \Exception
     * @return bool|int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        // add 1 to max position
        if (!$this->cms->id) {
            // todo position might have a different field name..
            $max_pos = sql_query('SELECT MAX(position) AS `max_pos` FROM `' . $this->cms->table . '`', 1);
            return $max_pos['max_pos'] + 1;
        }

        return $value ?: false;
    }
}
