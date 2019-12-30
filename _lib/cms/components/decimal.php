<?php
namespace cms;

class decimal extends component
{
	public $field_type = 'number';
	
	function value($value) {
        $value = number_format($value, 2);
		return $value;
	}
	
	function is_valid($value) {
		return is_numeric($value);
	}
}