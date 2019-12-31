<?php
namespace cms;

class ip extends component
{
	function value($value) {
		global $cms;
		
        if (!$cms->id) {
            return $_SERVER['REMOTE_ADDR'];
        } elseif (!$data[$field_name]) {
            return false;
        }
	}
}