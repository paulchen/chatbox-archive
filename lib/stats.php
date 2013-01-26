<?php
// TODO check if included

require_once(dirname(__FILE__) . '/common.php');

function ex_aequo2(&$data) {
	ex_aequo($data, 2);
}

function ex_aequo3(&$data) {
	ex_aequo($data, 3);
}

function ex_aequo(&$data, $col) {
	$last_value = -1;
	foreach($data[0] as &$row) {
		$keys = array_keys($row);
		$first_row = $keys[0];
		$compare_row = $keys[$col];
		if($row[$compare_row] == $last_value) {
			$row[$first_row] = '';
		}
		$last_value = $row[$compare_row];
	}
	unset($row);
}

$last_update = -1;
foreach($queries as $index => $query) {
	if(!isset($query['params'])) {
		$query['params'] = array();
	}
	$hash = sha1($query['query'] . serialize($query['params']));
	$memcached_key = "${memcached_prefix}_stats_$hash";
	$memcached_data = $memcached->get($memcached_key);
	if($memcached_data) {
		$last_update = max($memcached_data['update'], $last_update);
		$data = $memcached_data['data'];
	}
	else {
		$data = db_query($query['query'], $query['params']);

		$memcached_data = array(
				'update' => time(),
				'data' => $data
			);
		// TODO magic number
		$memcached->set($memcached_key, $memcached_data, 300+rand(0,100));

		$last_update = time();
	}

	if(isset($query['processing_function'])) {
		foreach($data as $key => &$value) {
			call_user_func($query['processing_function'], array(&$value));
		}
		unset($value);
	}

	if(isset($query['processing_function_all'])) {
		call_user_func($query['processing_function_all'], array(&$data));
	}
	$queries[$index]['data'] = $data;
}

header('Content-Type: application/xhtml+xml; charset=utf-8');
require_once(dirname(__FILE__) . '/../templates/pages/stats.php');

