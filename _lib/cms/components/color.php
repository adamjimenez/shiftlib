<?php
namespace cms;

class color extends component
{
	public $field_type = 'color';
	public $field_sql = "VARCHAR( 7 ) NOT NULL DEFAULT ''";
}