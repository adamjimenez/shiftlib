<?php
namespace cms;

class integer extends component
{
	public $field_type = 'number';
	public $field_sql = "INT";
	
	function value($value) {
        $value = number_format($value);
		return $value;
	}
	
	function is_valid($value) {
		return is_numeric($value);
	}
	
	function conditions_to_sql($field_name, $value, $func = '', $table_prefix='') {
		// todo strict func checking
		
        $pos = strrpos($value, '-');

        if ($func) {
            $where = $table_prefix . $field_name . ' ' . escape($func) . " '" . escape($value) . "'";
        } elseif ($pos > 0) {
            $min = substr($value, 0, $pos);
            $max = substr($value, $pos + 1);

            $where = "(".
                $table_prefix.$field_name . " >= '" . escape($min) . "' AND ".
                $table_prefix.$field_name . " <= '" . escape($max) . "'
            )";
        } else {
            $where = $table_prefix . $field_name . " = '" . escape($value) . "'";
        }
        
        return $where;
	}
	
	// applies any cleanup before saving
	function format_value($value) {
		return (int)$value;
	}
}