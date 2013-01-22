<?php
require_once('config.php');

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
$mysqli->query('SET NAMES utf8');

/* HTTP basic authentication */
if(!defined('STDIN') && !isset($argc)) {
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		noauth();
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	$stmt = $mysqli->prepare('SELECT hash FROM accounts WHERE username = ?');
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$stmt->bind_result($db_hash);
	$found = false;
	while($stmt->fetch()) {
		$found = true;
	}
	$stmt->close();

	$hash = crypt($password, $db_hash);
	if($hash != $db_hash) {
		noauth();
	}
}

$memcached = new Memcached();
foreach($memcached_servers as $server) {
	$memcached->addServer($server['ip'], $server['port']);
}

function noauth() {
	header('WWW-Authenticate: Basic realm="Access restricted"');
	header('HTTP/1.0 401 Unauthorized');
	die();
}

function unicode_character($matches) {
	if(($matches[1] == 0x9) || ($matches[1] == 0xA) || ($matches[1] == 0xD) ||
			(($matches[1] >= 0x20) && ($matches[1] <= 0xD7FF)) ||
			(($matches[1] >= 0xE000) && ($matches[1] <= 0xFFFD)) ||
			(($matches[1] >= 0x10000) && ($matches[1] <= 0x10FFFF))) {
		return $matches[0];
	}
	else {
		return ' ';
	}

}

function get_setting($key) {
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT value FROM settings WHERE `key` = ?');
	$stmt->bind_param('s', $key);
	$stmt->execute();
	$stmt->bind_result($value);
	$stmt->fetch();
	$stmt->close();

	return $value;
}

function set_setting($key, $value) {
	global $mysqli;

	$stmt = $mysqli->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?');
	$stmt->bind_param('sss', $key, $value, $value);
	$stmt->execute();
	$stmt->close();
}


