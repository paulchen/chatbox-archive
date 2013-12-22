<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

if(!isset($_REQUEST['user'])) {
	die();
}

$rows = db_query('SELECT UNIX_TIMESTAMP(s.date) last_seen
		FROM shouts s
			JOIN users u ON (s.user = u.id)
		WHERE u.name = ?
		ORDER BY s.epoch DESC, s.id DESC
		LIMIT 1', array($_REQUEST['user']));
if(count($rows) == 0) {
	echo 'NULL';
}
else {
	echo $rows[0]['last_seen'];
}

log_data();

