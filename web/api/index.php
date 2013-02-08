<?php

chdir(dirname(__FILE__) . '/../../');
require_once('./lib/common.php');

function get_parameter($name, $regex, $default) {
	if(!isset($_GET[$name])) {
		return $default;
	}
	$value = trim($_GET[$name]);
	if(!preg_match($regex, $value)) {
		return $default;
	}
	return $value;
}

$offset = get_parameter('offset', '/^[0-9]+$/', '0');
$limit = get_parameter('limit', '/^[0-9]+$/', '100');
$text = get_parameter('text', '/./', '');
$user = get_parameter('user', '/./', '');
$date = get_parameter('date', '/./', '');

$message_data = get_messages($text, $user, $date, $offset, $limit);

header('Content-Type: application/xhtml+xml; charset=utf-8');
require_once('templates/api/success.php');

