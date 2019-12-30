<?php
namespace cms;

class integer extends component
{
	public $field_type = 'number';
	
	function value($value) {
        $value = number_format($value);
		return $value;
	}
	
	function is_valid($value) {
		return is_numeric($value);
	}
}