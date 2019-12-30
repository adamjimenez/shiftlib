<?php
namespace cms;

class tel extends component
{
	public $field_type = 'tel';
	
	function is_valid($value) {
		return is_tel($value);
	}
}