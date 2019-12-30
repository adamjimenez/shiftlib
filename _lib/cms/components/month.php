<?php
namespace cms;

class month extends date
{
	function field($field_name, $value = '', $options = []) {
        ?>
            <input type="text" class="month" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && '0000-00-00' != $value) ? $value : '';?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?=$options['attribs'] ?: 'style="width:75px;"';?> />
        <?php
	}
	
	function value($value) {
        if ('0000-00-00' != $value and '' != $value) {
            $value = dateformat('F Y', $value);
        }
		return $value;
	}
	
	function format_value($value) {
        if ($value) {
            $value .= '-01';
        }
		return $value;
	}
	
	function is_valid($value) {
        return Base::is_valid($value);
    }
}