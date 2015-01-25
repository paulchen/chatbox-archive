<?php

chdir(dirname(__FILE__));
require_once('../lib/common.php');

$query = 'SELECT primary_id FROM shouts ORDER BY primary_id ASC';
$data = db_query($query, array());

$total = count($data);
$index = 0;
	
$start_time = time();
foreach($data as $row) {
	$index++;
	if($index % 100 == 0) {
		$now = time();
		$elapsed = $now-$start_time;
		$total_time = ($elapsed/$index)*$total;
		$remaining = round($total_time-$elapsed);

		echo "Processing message " . $row['primary_id'] . "... ETA: $remaining seconds\n";

		$db_queries = array();
	}
	process_smilies($row['primary_id']);
}

