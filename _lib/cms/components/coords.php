<?php
namespace cms;

class coords extends component
{
	public $field_sql = "POINT";
	
	function field($field_name, $value = '', $options = []) {
		?>
        <input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars(substr($value, 6, -1));?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="50" <?=$options['attribs'];?> <?php if ($options['placeholder']) { ?>placeholder="<?=$options['placeholder'];?>"<?php } ?>>
		<?
	}
	
	function value($value) {
        $value = '<input type="text" class="map" name="' . $field_name . '" value="' . htmlspecialchars(substr($value, 6, -1)) . '" size="50" ' . $attribs . '>';
		return $value;
	}
}