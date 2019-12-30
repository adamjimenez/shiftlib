<?php
namespace cms;

class datetime extends component
{
	function field($field_name, $value = '', $options = []) {
        if ($value) {
            $date = explode(' ', $value);
        }
        ?>
            <input type="date" name="<?=$field_name;?>" value="<?=($date[0] and '0000-00-00' != $date[0]) ? $date[0] : '';?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?=$options['attribs'] ?: '';?> />
            <input type="time" step="1" name="time[<?=$field_name;?>]" value="<?=$date[1];?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs'];?>>
        <?php
	}
	
	function value($value) {
        if ('0000-00-00 00:00:00' != $value) {
            $date = explode(' ', $value);
            $value = dateformat('d/m/Y', $date[0]) . ' ' . $date[1];
        }
		return $value;
	}
}