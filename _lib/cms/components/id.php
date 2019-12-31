<?php
namespace cms;

class id extends integer
{
	public $field_sql = null;
	
	function format_value($value) {
		return false;
	}
}