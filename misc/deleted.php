<?php
require_once('../lib/common.php');

$file = file_get_contents('abc');
$ids = explode("\n", $file);
$min_id = 500000;
$max_id = 0;
foreach($ids as $id) {
	if(is_numeric($id) && $id < $min_id) {
		$min_id = $id;
	}
	if(is_numeric($id) && $id > $max_id) {
		$max_id = $id;
	}
}

$count = 0;
for($min=$min_id; $min<=$max_id; $min+=10000) {
	$max = $min+10000;
	$temp_ids = array();
	foreach($ids as $id) {
		if($id >= $min-1000 && $id <= $max+1000) {
			$temp_ids[] = $id;
		}
	}

	#$data = db_query('SELECT id FROM shouts WHERE id >= ? AND id < ? AND deleted = ?', array($min_id, 452470, 0));
	$data = db_query('SELECT id FROM shouts WHERE id >= ? AND id < ? AND deleted = ?', array($min, $max, 0));
	$data = array_map(function($a) { return $a['id']; }, $data);

	foreach($data as $row) {
		$count++;
		if($count % 100 == 0) {
			echo "Checking id $row\n";
		}

		$found = false;
		foreach($temp_ids as $index => $id) {
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

	unset($data);
	unset($temp_ids);
}

