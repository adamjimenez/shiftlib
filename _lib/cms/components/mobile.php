<?php
namespace cms;

class mobile extends component
{
	function is_valid($value) {
		return format_mobile($value) !== false;
	}
	
	function format_value($value) {
		return format_mobile($value);
	}
}