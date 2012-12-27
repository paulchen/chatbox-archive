<?php
/* HTTP basic authentication */
if(!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Access restricted"');
	header('HTTP/1.0 401 Unauthorized');
	die();
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
	// TODO duplicate code
	header('WWW-Authenticate: Basic realm="Access restricted"');
	header('HTTP/1.0 401 Unauthorized');
	die();
}

