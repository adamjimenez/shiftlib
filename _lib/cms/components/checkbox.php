<?php
namespace cms;

class checkbox extends component
{
	public $field_sql = "TINYINT";
	
	function field($field_name, $value = '', $options = []) {
    ?>
        <input type="checkbox" name="<?=$field_name;?>" value="1" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?php if ($value) { ?>checked<?php } ?>  <?=$options['attribs'];?> />
    <?php
	}
	
	function value($value, $name) {
		$value = $value ? 'Yes' : 'No';
        return $value;
	}
}