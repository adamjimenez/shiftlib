<?php
namespace cms;

abstract class component
{
	public $field_type = 'text';
	
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
}