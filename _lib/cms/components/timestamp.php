<?php
namespace cms;

class timestamp extends component
{
	public $field_type = 'hidden';
	public $field_sql = "TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
}