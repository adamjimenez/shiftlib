<?php
namespace cms;

class password extends component
{
	public $field_type = 'password';
	public $preserve_value = true;
	
	function value($value) {
		return '';
	}
	
	function format_value($value) {
		global $auth;
		
		// add 1 to max position
		if ($auth->hash_password) {
			$value = $auth->create_hash($value);
		}
		
		return $value ?: false;
	}
}