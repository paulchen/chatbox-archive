<?php

require_once('../lib/common.php');

$query = 'SELECT id, epoch FROM shouts';
$data = db_query($query, array());

foreach($data as $row) {
//	if($row['id'] % 100 == 0) {
		echo "Processing message " . $row['id'] . ", epoch " . $row['epoch'] . "...\n";
//	}
	process_words($row['id'], $row['epoch']);
}

