<?php
require_once('lib/common.php');

$file = file_get_contents('abc');
$ids = explode("\n", $file);
$min_id = 500000;
foreach($ids as $id) {
	if(is_numeric($id) && $id < $min_id) {
		$min_id = $id;
	}
}

$data = db_query('SELECT id FROM shouts WHERE id >= ? AND id < ? AND deleted = ?', array($min_id, 446020, 0));
$data = array_map(function($a) { return $a['id']; }, $data);

$count = 0;
foreach($data as $row) {
	$count++;
	if($count % 100 == 0) {
		echo "Checking id $row\n";
	}

	$found = false;
	foreach($ids as $index => $id) {
		if($row == $id) {
//			unset($ids[$index]);
			$found = true;
			break;
		}
	}

	if(!$found) {
		echo "$row\n";
	}
}


