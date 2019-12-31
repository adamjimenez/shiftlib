<?php
namespace cms;

class timestamp extends date
{
	public $field_type = 'hidden';
	public $field_sql = "TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
	
	function is_valid($value) {
        return Base::is_valid($value);
    }
	
	function format_value($value) {
		return false;
	}
}