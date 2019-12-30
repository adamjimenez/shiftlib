<?php
namespace cms;

class decimal extends component
{
	public $field_type = 'number';
	
	function value($value) {
        $value = number_format($value, 2);
		return $value;
	}
}