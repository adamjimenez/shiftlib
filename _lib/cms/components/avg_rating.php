<?php
namespace cms;

class avg_rating extends component
{
	public $field_sql = "TINYINT";
	
    // rating widget options
    public $rating_opts = [
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ];
	
	function field($field_name, $value = '', $options = []) {
        ?>
            <select name="<?=$field_name;?>" class="rating" data-section="<?=$this->section;?>" data-item="<?=$this->content['id'];?>" <?php if ('avg-rating' == $type) {?>data-avg='data-avg'<?php } ?> <?=$attribs;?>>
                <option value="">Choose</option>
                <?=html_options($this->opts['rating'], $value, true);?>
            </select>
        <?php
	}
}