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
	
	function conditions_to_sql($field_name, $value, $func = '', $table_prefix='') {
        return '`' . $field_name . "`!='0000-00-00' AND " .
        "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(" . $field_name . ", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(" . $field_name . ", '00-%m-%d')) LIKE '" . escape($value) . ' ';
	}
}