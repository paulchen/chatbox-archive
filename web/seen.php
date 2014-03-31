<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

if(!isset($_REQUEST['user'])) {
	die();
}

$rows = db_query('SELECT s.date last_seen
		FROM shouts s
			JOIN users u ON (s.user_id = u.id)
		WHERE u.name = ?
		ORDER BY s.epoch DESC, s.id DESC
		LIMIT 1', array($_REQUEST['user']));
if(count($rows) == 0) {
	echo 'NULL';
}
else {
	$datetime = new DateTime($rows[0]['last_seen'], new DateTimeZone('Europe/Vienna'));
	echo $datetime->getTimestamp()+3600;
}

log_data();

