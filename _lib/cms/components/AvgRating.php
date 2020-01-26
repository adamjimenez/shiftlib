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
        <select name="<?= $field_name; ?>" class="rating" data-section="<?= $cms->section; ?>" data-item="<?= $cms->content['id']; ?>" data-avg="data-avg" <?= $options['attribs']; ?>>
            <option value="">Choose</option>
            <?= html_options($this->rating_opts, $value, true); ?>
        </select>
        <?php
    }
}
