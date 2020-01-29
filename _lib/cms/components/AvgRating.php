<?php

namespace cms\components;

use cms\ComponentInterface;

class AvgRating extends Rating implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TINYINT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', $options = []): string
    {
        return '<select name="' . $fieldName . '" class="rating" data-section="' . $this->cms->section . '" data-item="' . $this->cms->content['id'] . '"  data-avg="data-avg" ' . $options['attribs'] . '>
            <option value="">Choose</option>
            ' . html_options(self::RATING_OPTS, $value, true) . '
        </select>';
    }
}
