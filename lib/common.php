<?php
$start_time = microtime(true);

require_once(dirname(__FILE__) . '/../config.php');

$db = new PDO("mysql:dbname=$db_name;host=$db_host", $db_user, $db_pass);
db_query('SET NAMES utf8');

/* HTTP basic authentication */
if(!defined('STDIN') && !isset($argc)) {
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		noauth();
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	$query = 'SELECT hash FROM accounts WHERE username = ?';
	$data = db_query($query, array($username));
	if(count($data) != 1) {
		noauth();
	}

	$hash = crypt($password, $data[0]['hash']);
	if($hash != $data[0]['hash']) {
		noauth();
	}
}

$memcached = new Memcached();
foreach($memcached_servers as $server) {
	$memcached->addServer($server['ip'], $server['port']);
}

function db_query($query, $parameters = array()) {
	global $db, $db_locked, $db_queries;

	$query_start = microtime(true);
	if(!($stmt = $db->prepare($query))) {
		$error = $db->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	// see https://bugs.php.net/bug.php?id=40740 and https://bugs.php.net/bug.php?id=44639
	foreach($parameters as $key => $value) {
		$stmt->bindValue($key+1, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
	}
	if(!$stmt->execute()) {
		$error = $stmt->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!$stmt->closeCursor()) {
		$error = $stmt->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	$query_end = microtime(true);

	if(preg_match('/LOCK TABLES/i', $query)) {
		$db_locked = true;
	}

	if(!isset($db_queries)) {
		$db_queries = array();
	}
	$db_queries[] = array('timestamp' => time(), 'query' => $query, 'parameters' => serialize($parameters), 'execution_time' => $query_end-$query_start);

	return $data;
}

function db_error($error, $stacktrace, $query, $parameters) {
	global $report_email, $email_from;

	ob_start();
	require(dirname(__FILE__) . '/../templates/mails/db_error.php');
	$message = ob_get_contents();
	ob_end_clean();

	// TODO duplicate code
	$headers = "From: $email_from\n";
	$headers .= "Content-Type: text/plain; charset = \"UTF-8\";\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";

	$subject = 'Database error';

	mail($report_email, $subject, $message, $headers);

	header('HTTP/1.1 500 Internal Server Error');
	echo "A database error has just occurred. Please don't freak out, the administrator has already been notified.";
	die();
}

function db_last_insert_id() {
	global $db;

	return $db->lastInsertId();
}

function log_data() {
	global $db_locked, $db_queries, $start_time;

	if($db_locked) {
		db_query('UNLOCK TABLES');
		$db_locked = false;
	}

	$end_time = microtime(true);

	$query = 'INSERT INTO requests (timestamp, url, ip, request_time, browser) VALUES (FROM_UNIXTIME(?), ?, ?, ?, ?)';
	db_query($query, array(time(), $_SERVER['REQUEST_URI'], $_SERVER['REMOTE_ADDR'], $end_time-$start_time, $_SERVER['HTTP_USER_AGENT']));
	$request_id = db_last_insert_id();

	$query = 'INSERT INTO queries (request, timestamp, query, parameters, execution_time) VALUES (?, FROM_UNIXTIME(?), ?, ?, ?)';

	/* don't use a foreach loop as this would create an endless loop because of db_query() appending each qwuery to $db_queries */
	/* subtract 1 as we do not want the 'INSERT INTO requests' query (see above) to be logged */
	$queries = count($db_queries)-1;
	for($a=0; $a<$queries; $a++) {
		$db_query = $db_queries[$a];
		db_query($query, array($request_id, $db_query['timestamp'], $db_query['query'], $db_query['parameters'], $db_query['execution_time']));
	}
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
	$query = 'SELECT value FROM settings WHERE `key` = ?';
	$data = db_query($query, array($key));

	return $data[0]['value'];
}

function set_setting($key, $value) {
	$query = 'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?';
	db_query($query, array($key, $value, $value));
}

function process_message_smiley($match) {
	global $found_smilies;

	if(mb_substr($match[0], 0, 1, 'UTF-8') == '"') {
		$match[0] = mb_substr($match[0], 1, mb_strlen($match[0], 'UTF-8')-1, 'UTF-8');
	}

	$full_url = 'http://www.informatik-forum.at/' . $match[0];
	$filename = basename($match[0]);

	$query = 'SELECT id FROM smilies WHERE filename = ?';
	$data = db_query($query, array($filename));
	if(count($data) == 0) {
		$query = 'INSERT INTO smilies (filename) VALUES (?)';
		db_query($query, array($filename));

		$query = 'SELECT id FROM smilies WHERE filename = ?';
		$data = db_query($query, array($filename));

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $full_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$gif = curl_exec($curl);
		curl_close($curl);

		file_put_contents("web/images/smilies/$filename", $gif);
	}

	$smiley_id = $data[0]['id'];
	if(!isset($found_smilies[$smiley_id])) {
		$found_smilies[$smiley_id] = 1;
	}
	else {
		$found_smilies[$smiley_id] = $found_smilies[$smiley_id] + 1;
	}
}

function process_smilies($id, $epoch) {
	global $found_smilies;

	$query = 'SELECT message FROM shouts WHERE id = ? AND epoch = ?';
	$data = db_query($query, array($id, $epoch));
	if(count($data) != 1) {
		return;
	}
	$message = $data[0]['message'];

	$found_smilies = array();
	$message = preg_replace_callback('+"/?(pics|images)/([no]b/)?smilies/[^"]*\.(gif|png|jpg)+i', 'process_message_smiley', $message);

	$query = 'SELECT smiley, `count` FROM shout_smilies WHERE shout_id = ? AND shout_epoch = ?';
	$data = db_query($query, array($id, $epoch));

	$diff = false;
	foreach($data as $row) {
		if(!isset($found_smilies[$row['smiley']]) || $found_smilies[$row['smiley']] != $row['count']) {
			$diff = true;
		}
	}
	foreach($found_smilies as $smiley => $count) {
		$found = false;
		foreach($data as $row) {
			if($row['smiley'] == $smiley && $row['count'] == $count) {
				$found = true;
			}
		}
		if(!$found) {
			$diff = true;
		}
	}

	if($diff) {
		$query = 'DELETE FROM shout_smilies WHERE shout_id = ? AND shout_epoch = ?';
		db_query($query, array($id, $epoch));

		$query = 'INSERT INTO shout_smilies (shout_id, shout_epoch, smiley, `count`) VALUES (?, ?, ?, ?)';
		foreach($found_smilies as $smiley => $count) {
			db_query($query, array($id, $epoch, $smiley, $count));
		}
	}
}

