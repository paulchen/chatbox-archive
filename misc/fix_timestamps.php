<?php
require_once('../lib/common.php');

$ranges = array(
	array('start' => 1, 'end' => 129206, 'year' => 2010),
	array('start' => 129207, 'end' => 211637, 'year' => 2011),
	array('start' => 211638, 'end' => 423572, 'year' => 2012),
	array('start' => 423573, 'end' => 500000, 'year' => 2013),
);

foreach($ranges as $index => $range) {
	$start = $range['start'];
	$end = $range['end'];

	$data = db_query("SELECT primary_id, date FROM shouts WHERE primary_id >= ? AND primary_id <= ? AND year <> ?", array($start, $end, $range['year']));
	$count = 0;
	foreach($data as $row) {
		$count++;
		$id = $row['primary_id'];
		$old_date = $row['date'];
		$new_date = preg_replace('/^201[0-9]/', $range['year'], $old_date);

		echo "$id $old_date $new_date\n";
		db_query('UPDATE shouts SET date = ?, year = ? WHERE primary_id = ?', array($new_date, $range['year'], $id));
	}
	$ranges[$index]['rows'] = $count;
}

print_r($ranges);

