<?php

namespace cms;

class avg_rating extends rating
{
    public $field_sql = 'TINYINT';

    public function field($field_name, $value = '', $options = [])
    {
        global $cms; ?>
        <select name="<?= $field_name; ?>" class="rating" data-section="<?= $cms->section; ?>" data-item="<?= $cms->content['id']; ?>" <?php if ('avg-rating' == $type) { ?>data-avg='data-avg'<?php } ?> <?= $attribs; ?>>
            <option value="">Choose</option>
            <?= html_options($this->rating_opts, $value, true); ?>
        </select>
        <?php
    }
}
