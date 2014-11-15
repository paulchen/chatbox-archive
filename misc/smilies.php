<?php

chdir(dirname(__FILE__));
require_once('../lib/common.php');

$query = 'SELECT primary_id FROM shouts';
$data = db_query($query, array());

foreach($data as $row) {
//	if($row['id'] % 100 == 0) {
		echo "Processing message " . $row['id'] . ", epoch " . $row['epoch'] . "...\n";
//	}
	process_smilies($row['primary_id']);
}

