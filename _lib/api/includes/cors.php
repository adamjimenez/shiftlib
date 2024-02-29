<?php
$http_origin = $_SERVER['HTTP_ORIGIN'] ?: 'localhost';

// Access-Control headers are received during OPTIONS requests
if (isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Origin: ' . $http_origin);
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400');
}
	
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])){
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
	}

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){
		header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	}
	exit;
}

// get json post
$request_body = file_get_contents('php://input');
if ($request_body) {
	$json = json_decode($request_body, true);
	if (is_array($json)) {
		$_POST = $json;
		$_REQUEST = array_merge($_REQUEST, $json);;
	}
}