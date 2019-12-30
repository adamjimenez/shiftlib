<?php
namespace cms;

class avg_rating extends component
{
	function field($field_name, $value = '', $options = []) {
        ?>
            <select name="<?=$field_name;?>" class="rating" data-section="<?=$this->section;?>" data-item="<?=$this->content['id'];?>" <?php if ('avg-rating' == $type) {?>data-avg='data-avg'<?php } ?> <?=$attribs;?>>
                <option value="">Choose</option>
                <?=html_options($this->opts['rating'], $value, true);?>
            </select>
        <?php
	}
}