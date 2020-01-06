<?
$cms->components = array('foo');

$cms_buttons[] = array(
	'section'=>'service',
	'page'=>'list',
	'label'=> 'Test button',
	'handler' => function() {
		die('hello world');
	}
);