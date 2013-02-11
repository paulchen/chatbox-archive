<?php

chdir(dirname(__FILE__) . '/../');
require_once('lib/common.php');

$query = 'SELECT id, epoch, UNIX_TIMESTAMP(date) date FROM shouts WHERE deleted = 0 ORDER BY epoch ASC, id ASC';
$data = db_query($query);

$periods = array(
	array('seconds' => 86400, 'name' => 'One day'),
	array('seconds' => 86400*7, 'name' => 'One week'),
	array('seconds' => 86400*30, 'name' => 'One month'),
	array('seconds' => 86400*365, 'name' => 'One year'),
);

foreach($periods as &$period) {
	$start = 0;
	$end = 0;

	$max = 0;
	$min = count($data);
	while($start < count($data)) {
		while($data[$end]['date']-$data[$start]['date'] < $period['seconds']) {
			$end++;
			if($end == count($data)) {
				break 2;
			}
		}

		if($end-$start > $max) {
			$max_start = $start;
			$max_end = $end;
			$max = $end-$start;
		}
		if($end-$start < $min) {
			$min_start = $start;
			$min_end = $end;
			$min = $end-$start;
		}

		$date = $data[$start]['date'];
		while($data[$start]['date'] == $date) {
			$start++;
		}
	}

	$query = 'SELECT u.name, COUNT(*) shouts FROM users u JOIN shouts s ON (u.id = s.user) WHERE ((? = ? AND s.id >= ? AND s.id < ?) OR (? < ? AND ((s.epoch = ? AND s.id >= ?) OR (s.epoch > ? AND s.epoch < ?) OR (s.epoch = ? AND s.id < ?)))) AND deleted = 0 GROUP BY u.id, u.name ORDER BY COUNT(*) DESC LIMIT 0, 1';
	$data2 = db_query($query, array($data[$min_start]['epoch'], $data[$min_end]['epoch'], $data[$min_start]['id'], $data[$min_end]['id'], $data[$min_start]['epoch'], $data[$min_end]['epoch'], $data[$min_start]['epoch'], $data[$min_start]['id'], $data[$min_start]['epoch'], $data[$min_end]['epoch'], $data[$min_end]['epoch'], $data[$min_end]['id']));
	$min_max_spammer = $data2[0];
	$data2 = db_query($query, array($data[$max_start]['epoch'], $data[$max_end]['epoch'], $data[$max_start]['id'], $data[$max_end]['id'], $data[$max_start]['epoch'], $data[$max_end]['epoch'], $data[$max_start]['epoch'], $data[$max_start]['id'], $data[$max_start]['epoch'], $data[$max_end]['epoch'], $data[$max_end]['epoch'], $data[$max_end]['id']));
	$max_max_spammer = $data2[0];

	$query = 'SELECT sm.id, sm.filename, COUNT(*) smilies FROM shouts s JOIN shout_smilies ss ON (s.epoch = ss.shout_epoch AND s.id = ss.shout_id) JOIN smilies sm ON (ss.smiley = sm.id) WHERE ((? = ? AND s.id >= ? AND s.id < ?) OR (? < ? AND ((s.epoch = ? AND s.id >= ?) OR (s.epoch > ? AND s.epoch < ?) OR (s.epoch = ? AND s.id < ?)))) AND deleted = 0 GROUP BY sm.id, sm.filename ORDER BY COUNT(*) DESC LIMIT 0, 1';
	$data2 = db_query($query, array($data[$min_start]['epoch'], $data[$min_end]['epoch'], $data[$min_start]['id'], $data[$min_end]['id'], $data[$min_start]['epoch'], $data[$min_end]['epoch'], $data[$min_start]['epoch'], $data[$min_start]['id'], $data[$min_start]['epoch'], $data[$min_end]['epoch'], $data[$min_end]['epoch'], $data[$min_end]['id']));
	$min_max_smiley = $data2[0];
	$data2 = db_query($query, array($data[$max_start]['epoch'], $data[$max_end]['epoch'], $data[$max_start]['id'], $data[$max_end]['id'], $data[$max_start]['epoch'], $data[$max_end]['epoch'], $data[$max_start]['epoch'], $data[$max_start]['id'], $data[$max_start]['epoch'], $data[$max_end]['epoch'], $data[$max_end]['epoch'], $data[$max_end]['id']));
	$max_max_smiley = $data2[0];

	$period['min'] = array('count' => $min, 'start_id' => $min_start, 'end_id' => $min_end, 'start_data' => $data[$min_start], 'end_data' => $data[$min_end], 'spammer' => $min_max_spammer, 'smiley' => $min_max_smiley);
	$period['max'] = array('count' => $max, 'start_id' => $max_start, 'end_id' => $max_end, 'start_data' => $data[$max_start], 'end_data' => $data[$max_end], 'spammer' => $max_max_spammer, 'smiley' => $max_max_smiley);

	$period['min']['end_data']['date'] = $period['min']['start_data']['date'] + $period['seconds'];
	$period['max']['end_data']['date'] = $period['max']['start_data']['date'] + $period['seconds'];
}
unset($period);

ob_start();
require_once('templates/pages/cached_stats.php');
$data = ob_get_contents();
ob_end_clean();

$memcached_key = "${memcached_prefix}_stats_min_max";
$memcached->set($memcached_key, $data, 5400);

