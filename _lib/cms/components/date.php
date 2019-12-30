<?php
namespace cms;

class date extends component
{
	public $field_sql = "DATE";
	
	function field($field_name, $value = '', $options = []) {
        ?>
        <input type="text" data-type="date" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && '0000-00-00' != $value) ? $value : '';?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> size="10" <?=$options['attribs'] ?: 'style="width:75px;"';?> autocomplete="off">
        <?php
	}
	
	function value($value) {
        if ('0000-00-00' != $value and '' != $value) {
            $value = dateformat('d/m/Y', $value);
        }
		return $value;
	}
	
	function is_valid($value) {
		return preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $value);
	}
	
	function conditions_to_sql($field_name, $value, $func = '=', $table_prefix='') {
		if ('now' == $value) {
		    $start = 'NOW()';
		} elseif ('month' == $func) {
		    $start = dateformat('mY', $value);
		} else {
		    $start = "'" . escape(dateformat('Y-m-d', $value)) . "'";
		}
		
		if ('month' == $func) {
		    $where = 'date_format(' . $table_prefix . $field_name . ", '%m%Y') = '" . escape($value) . "'";
		} elseif ('year' == $func) {
		    $where  = 'date_format(' . $table_prefix . $field_name . ", '%Y') = '" . escape($value) . "'";
		} elseif ($value and $conditions['end'][$field_name]) {
		    $end = escape($conditions['end'][$field_name]);
		
		    $where = '(' . $table_prefix . $field_name . " >= " . $start . ' AND ' . $table_prefix . $field_name . " <= '" . $end . "')";
		} elseif ($func) {
		    $where = $table_prefix . $field_name . ' ' . escape($func) . ' ' . $start;
		}
		
		return $where;
	}
}