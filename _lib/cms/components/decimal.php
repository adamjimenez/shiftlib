<?php
namespace cms;

class decimal extends integer
{
	public $field_sql = "DECIMAL( 8,2 )";
	
	function value($value) {
        $value = number_format($value, 2);
		return $value;
	}
}