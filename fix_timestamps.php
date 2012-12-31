<?php
require_once('common.php');

$ranges = array(
	array('start' => 1, 'end' => 129206, 'offset' => -2),
	array('start' => 129207, 'end' => 211637, 'offset' => -1),
	array('start' => 211638, 'end' => 500000, 'offset' => 0),
);

foreach($ranges as $range) {
	$offset = $range['offset'];
	if($offset == 0) {
		continue;
	}

	$start = $range['start'];
	$end = $range['end'];

	$stmt = $mysqli->prepare('SELECT id, date FROM shouts WHERE id >= ? AND id <= ?');
	$stmt->bind_param('ii', $start, $end);
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($id, $old_date);

	$stmt2 = $mysqli->prepare('UPDATE shouts SET date = ? WHERE id = ?');
	while($stmt->fetch()) {
		$date = new DateTime($old_date, new DateTimeZone('GMT'));
		$date = $date->modify("$offset year");
		$new_date = $date->format('Y-m-d H:i:s');

		$stmt2->bind_param('si', $new_date, $id);
		$stmt2->execute();
	}
	$stmt2->close();
	$stmt->close();
}

$mysqli->close();

