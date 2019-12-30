<?php
namespace cms;

class postcode extends component
{
	function is_valid($value) {
		return format_postcode($value) !== false;
	}
	
	function format_value($value) {
		return format_postcode($value);
	}
}