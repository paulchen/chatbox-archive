<?php
$start_time = microtime(true);

require_once(dirname(__FILE__) . '/../config.php');
require_once('Mail/mime.php');
require_once('Mail.php');

$db = new PDO("pgsql:dbname=$db_name;host=$db_host", $db_user, $db_pass);

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
	global $db, $db_queries;

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

	if(!isset($db_queries)) {
		$db_queries = array();
	}
	$db_queries[] = array('timestamp' => time(), 'query' => $query, 'parameters' => serialize($parameters), 'execution_time' => $query_end-$query_start);

	return $data;
}

function db_error($error, $stacktrace, $query, $parameters) {
	global $report_email, $email_from;

	header('HTTP/1.1 500 Internal Server Error');
	echo "A database error has just occurred. Please don't freak out, the administrator has already been notified.\n";

	$params = array(
			'ERROR' => $error,
			'STACKTRACE' => dump_r($stacktrace),
			'QUERY' => $query,
			'PARAMETERS' => dump_r($parameters),
			'REQUEST_URI' => (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : 'none',
		);
	send_mail('db_error.php', 'Database error', $params, true);
}

function dump_r($variable) {
	ob_start();
	print_r($variable);
	$data = ob_get_contents();
	ob_end_clean();

	return $data;
}

function db_last_insert_id() {
	global $db;

	$data = db_query('SELECT lastval() id');
	return $data[0]['id'];
}

function log_data() {
	global $db_queries, $start_time;

	$end_time = microtime(true);

	$query = 'INSERT INTO requests (timestamp, url, ip, request_time, browser, username) VALUES (FROM_UNIXTIME(?), ?, ?, ?, ?, ?)';
	db_query($query, array(time(), $_SERVER['REQUEST_URI'], $_SERVER['REMOTE_ADDR'], $end_time-$start_time, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', $_SERVER['PHP_AUTH_USER']));
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
	$query = 'SELECT value FROM settings WHERE "key" = ?';
	$data = db_query($query, array($key));

	// TODO undefined setting?
	return $data[0]['value'];
}

function set_setting($key, $value) {
	// TODO transaction
	$query = 'UPDATE settings SET value = ? WHERE "key" = ?;';
	db_query($query, array($value, $key));
	$query = 'INSERT INTO settings ("key", value) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM settings WHERE "key" = ?)';
	db_query($query, array($key, $value, $key));
//	$query = 'INSERT INTO settings ("key", value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?';
//	db_query($query, array($value, $key, $key, $value, $key));
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

	$query = 'SELECT smiley, "count" FROM shout_smilies WHERE shout_id = ? AND shout_epoch = ?';
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

		$query = 'INSERT INTO shout_smilies (shout_id, shout_epoch, smiley, "count") VALUES (?, ?, ?, ?)';
		foreach($found_smilies as $smiley => $count) {
			db_query($query, array($id, $epoch, $smiley, $count));
		}
	}
}

function process_words($id, $epoch) {
	global $found_smilies;

	$query = 'SELECT message FROM shouts WHERE id = ? AND epoch = ?';
	$data = db_query($query, array($id, $epoch));
	if(count($data) != 1) {
		return;
	}
	$message = $data[0]['message'];
	$message = str_replace(array(',', '.', '!', '?'), array('', '', '', ''), $message);

	$words = preg_split('/[\s]+/', $message);
	$found_words = array();
	foreach($words as $index => $word) {
		$word = mb_strtolower(trim($word), 'UTF-8');
		if(preg_match('/^[a-z]+$/', $word)) {
			$query = 'SELECT id FROM words WHERE word = ?';
			$data = db_query($query, array($word));
			if(count($data) == 0) {
				$query = 'INSERT INTO words (word) VALUES (?)';
				db_query($query, array($word));

				$query = 'SELECT id FROM words WHERE word = ?';
				$data = db_query($query, array($word));
			}

			$word_id = $data[0]['id'];
			if(!isset($found_words[$word_id])) {
				$found_words[$word_id] = 1;
			}
			else {
				$found_words[$word_id] = $found_words[$word_id] + 1;
			}
		}
	}

	$query = 'SELECT word, "count" FROM shout_words WHERE shout_id = ? AND shout_epoch = ?';
	$data = db_query($query, array($id, $epoch));

	$diff = false;
	foreach($data as $row) {
		if(!isset($found_words[$row['word']]) || $found_words[$row['word']] != $row['count']) {
			$diff = true;
		}
	}
	foreach($found_words as $word => $count) {
		$found = false;
		foreach($data as $row) {
			if($row['word'] == $word && $row['count'] == $count) {
				$found = true;
			}
		}
		if(!$found) {
			$diff = true;
		}
	}

	if($diff) {
		$query = 'DELETE FROM shout_words WHERE shout_id = ? AND shout_epoch = ?';
		db_query($query, array($id, $epoch));

		$query = 'INSERT INTO shout_words (shout_id, shout_epoch, word, "count") VALUES (?, ?, ?, ?)';
		foreach($found_words as $word => $count) {
			db_query($query, array($id, $epoch, $word, $count));
		}
	}
}

function clean_text($message) {
	// TODO scan for < and > inside href attributes

	$message = preg_replace_callback('+<img src="(/?(pics|images)/([no]b/)?smilies/[^"]*\.(gif|png|jpg))+i', function($match) { return '<img src="images/smilies/' . basename($match[1]); }, $message);
	$message = str_replace('/http:', 'http:', $message);
	$message = str_replace(' target="_blank"', '', $message);
	$message = str_replace(' border="0"', '', $message);
	$message = str_replace('"style="', '" style="', $message);
	$message = str_replace('</A>', '</a>', $message);
	for($a=14; $a<32; $a++) {
		$message = str_replace(chr($a), '', $message);
	}

	$message = preg_replace_callback('/&#([0-9]+);/', 'unicode_character', $message);
	$message = preg_replace('/color=(#......)/', 'color="\1"', $message);

	$message = preg_replace('/<a /', '<a target="_blank" ', $message);

	// TODO problems with <embed> tag?
	$message = preg_replace('/width=&quot;([0-9]+)&quot; height=&quot;([0-9]+)&quot;/', 'width="\\1" height="\\2"', $message);
	$message = preg_replace('/x-shockwave-flash"[^"<>]+>/', 'x-shockwave-flash">', $message);
	$message = preg_replace('/<embed src="/', '<embed src="proxy.php?url=', $message);
	$message = preg_replace('/<img src="http:/', '<img src="proxy.php?url=http:', $message);
	$message = preg_replace('/<img style="max-height: 50px" src="http:/', '<img style="max-height: 50px" src="proxy.php?url=http:', $message);
	$message = preg_replace('/<img alt="([^"]+)" src="/', '<img alt="\1" src="proxy.php?url=', $message);

	$message = str_replace(chr(2), '', $message);

	return $message;
}

function get_messages($text = '', $user = '', $date = '', $offset = 0, $limit = 100) {
	$filters = array('deleted = 0');
	$params = array();
	if($text != '') {
		$filters[] = 's.message LIKE ?';
		$params[] = "%$text%";
	}
	if($user != '') {
		$filters[] = 'u.name = ?';
		$params[] = $user;
	}
	if($date != '') {
		$filters[] = "TO_CHAR(s.date+INTERVAL '1 hour', 'YYYY-MM-DD') = ?";
		$params[] = $date;
	}

	$filter = implode(' AND ', $filters);
	$query = "SELECT s.id id, s.epoch epoch, s.date date, c.color color, u.id user_id, u.name user_name, message, COUNT(sr.revision) revision_count
			FROM shouts s
				JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id)
				LEFT JOIN shout_revisions sr ON (s.id = sr.id AND s.epoch = sr.epoch)
			WHERE $filter
			GROUP BY s.id, s.epoch, s.date, color, user_id, user_name, message
			ORDER BY s.epoch DESC, s.id DESC
			OFFSET ? LIMIT ?";
	$params[] = intval($offset);
	$params[] = intval($limit);
	$db_data = db_query($query, $params);

	$data = array();
	foreach($db_data as $row) {
		$datetime = new DateTime($row['date'], new DateTimeZone('Europe/London'));
		$datetime->setTimezone((new DateTime())->getTimezone());
		$formatted_date = $datetime->format('[d-m-Y H:i]');
		$color = $row['color'];
		$color = ($color == '-') ? 'user' : $color;
		$user_name = $row['user_name'];

		// TODO remove from here?
		$link = '?user=' . urlencode($user_name) . "&amp;limit=$limit";
		if($text != '') {
			$link .= '&amp;text=' . urlencode($text);
		}

		$message = clean_text($row['message']);

		$revisions = array();
		if($row['revision_count'] > 0) {
			$sql = 'SELECT sr.revision revision, sr.text "text", sr.date "date", sr.user "user", c.color color, u.name user_name
					FROM shout_revisions sr
						JOIN users u ON (sr.user = u.id) JOIN user_categories c ON (u.category = c.id)
					WHERE sr.id = ? AND sr.epoch = ?
					ORDER BY sr.revision DESC';
			$revisions = db_query($sql, array($row['id'], $row['epoch']));

			foreach($revisions as &$revision) {
				$revision['text'] = clean_text($revision['text']);
				$revision['color'] = ($revision['color'] == '-') ? 'user' : $revision['color'];

				$datetime = new DateTime($row['date'], new DateTimeZone('Europe/London'));
				$datetime->setTimezone((new DateTime())->getTimezone());
				$revision['date'] = $datetime->format('[d-m-Y H:i]');
			}
		}

		$data[] = array('unixdate' => $datetime->getTimestamp(), 'date' => $formatted_date, 'color' => $color, 'user_id' => $row['user_id'], 'user_name' => $user_name, 'message' => $message, 'user_link' => $link, 'id' => $row['id'], 'epoch' => $row['epoch'], 'revisions' => $revisions);
	}

	$query = 'SELECT COUNT(*) shouts FROM shouts WHERE deleted = 0';
	$db_data = db_query($query);
	$total_shouts = $db_data[0]['shouts'];

	$query = "SELECT COUNT(*) shouts FROM shouts s JOIN users u ON (s.user = u.id) WHERE $filter";
	array_pop($params);
	array_pop($params);
	$db_data = db_query($query, $params);
	$filtered_shouts = $db_data[0]['shouts'];

	$page_count = ceil($filtered_shouts/$limit);

	return array(
		'messages' => $data,
		'filtered_shouts' => $filtered_shouts,
		'total_shouts' => $total_shouts,
		'page_count' => $page_count,
	);
}

function send_mail($template, $subject, $parameters = array(), $fatal = false, $attachments = array()) {
	global $email_from, $report_email;

	if(strpos($template, '..') !== false) {
		die();
	}

	$message = file_get_contents(dirname(__FILE__) . "/../templates/mails/$template");

	$patterns = array();
	$replacements = array();
	foreach($parameters as $key => $value) {
		$patterns[] = "[$key]";
		$replacements[] = $value;
	}
	$message = str_replace($patterns, $replacements, $message);

	$headers = array(
			'From' => $email_from,
			'To' => $report_email,
			'Subject' => $subject,
		);

	$mime = &new Mail_Mime(array('text_charset' => 'UTF-8'));
	$mime->setTXTBody($message);
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	foreach($attachments as $attachment) {
		$mime->addAttachment($attachment, finfo_file($finfo, $attachment));
	}

	$mail =& Mail::factory('smtp');
	$mail->send($report_email, $mime->headers($headers), $mime->get());

	if($fatal) {
		// TODO HTTP error code/message
		die();
	}
}

function xml_validate($data) {
	$document = new DOMDocument;
	$xml_error = false;
	@$document->LoadXML($data) or $xml_error = true;
	if($xml_error) {
		$filename = tempnam($tmpdir, 'api_');
		file_put_contents($filename, $data);

		$parameters = array('REQUEST_URI' => $_SERVER['REQUEST_URI']);
		$attachments = array($filename);
		send_mail('validation_error.php', 'Validation error', $parameters, false, $attachments);

		unlink($filename);
	}

	return $data;
}


