<?php
namespace cms;

class datetime extends component
{
	public $field_sql = "DATETIME";
	
	function field($field_name, $value = '', $options = []) {
        ?>
        <input type="datetime-local" name="<?=$field_name;?>" value="<?=$value;?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?=$options['attribs'] ?: '';?>>
        <?php
	}
	
	function value($value) {
		return $value;
	}
	
	function format_value($value) {
        if ($value) {
            $value .= ':00';
        }
		return $value;
	}
}