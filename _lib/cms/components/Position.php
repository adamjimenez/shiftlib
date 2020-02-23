<?php

namespace cms\components;

use cms\ComponentInterface;

class Position extends Integer implements ComponentInterface
{
    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @throws \Exception
     * @return bool|int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        // add 1 to max position
        if (!$this->cms->getId()) {
            // todo position might have a different field name..
            $maxPos = sql_query('SELECT MAX(position) AS `max_pos` FROM `' . $this->cms->table . '`', 1);
            return $maxPos['max_pos'] + 1;
        }

        return $value ?: false;
    }
}
