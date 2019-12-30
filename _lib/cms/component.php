<?php
namespace cms;

abstract class component
{
	public $field_type = 'text';
	public $field_sql = "VARCHAR( 140 ) NOT NULL DEFAULT ''";
	
	function component() {
	}
	
	function field($field_name, $value = '', $options = []) {
		?>
		<input type="<?=$this->field_type;?>" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?php if ($options['placeholder']) { ?>placeholder="<?=$options['placeholder'];?>"<?php } ?> <?=$options['attribs'];?>>
		<?
	}
	
	function value($value, $name = '') {
		return $value;
	}
	
	function is_valid($value) {
		return true;
	}
	
	function format_value($value) {
		return strip_tags($value);
	}
}