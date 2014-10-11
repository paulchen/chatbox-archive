<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

if(!isset($_REQUEST['user'])) {
	die();
}

$rows = db_query('SELECT s.date last_seen, s.id id, s.epoch epoch
		FROM shouts s
			JOIN users u ON (s.user_id = u.id)
		WHERE LOWER(u.name) = LOWER(?)
		ORDER BY s.epoch DESC, s.id DESC
		LIMIT 1', array($_REQUEST['user']));
if(count($rows) == 0) {
	echo 'NULL';
}
else {
	$datetime = new DateTime($rows[0]['last_seen'], new DateTimeZone('Europe/Vienna'));
	echo $datetime->getTimestamp()+3600;
	if(isset($_REQUEST['version']) && $_REQUEST['version'] == '2') {
		echo " {$rows[0]['id']} {$rows[0]['epoch']}";
	}
}

log_data();

