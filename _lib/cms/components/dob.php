<?php
namespace cms;

class dob extends component
{
	public $field_sql = "DATE";
	
	function field($field_name, $value = '', $options = []) {
        ?>
            <input type="text" data-type="dob" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && '0000-00-00' != $value) ? $value : '';?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?=$options['attribs'] ?: 'style="width:75px;"';?>>
        <?php
	}
	
	function value($value) {
        if ('0000-00-00' != $value and '' != $value) {
            $age = age($value);
            $value = dateformat('d/m/Y', $value);
        }

        $value = $value . ' ('.$age.')';
		return $value;
	}
}