<?php
namespace cms;

class ip extends component
{
	function format_value($value) {
		global $cms;
		
        if (!$cms->id) {
            return $_SERVER['REMOTE_ADDR'];
        } else {
            return false;
        }
	}
}