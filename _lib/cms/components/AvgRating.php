<?php

namespace cms\components;

use cms\ComponentInterface;

class AvgRating extends Rating implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'TINYINT';
    }

    public function field($field_name, $value = '', $options = []): void
    {
        global $cms; ?>
        <select name="<?= $field_name; ?>" class="rating" data-section="<?= $cms->section; ?>" data-item="<?= $cms->content['id']; ?>" <?php if ('avg-rating' == $type) { ?>data-avg='data-avg'<?php } ?> <?= $attribs; ?>>
            <option value="">Choose</option>
            <?= html_options($this->rating_opts, $value, true); ?>
        </select>
        <?php
    }
}
