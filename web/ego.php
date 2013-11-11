<?php

function init_ego(&$user_egos, $id) {
	if(!isset($user_egos[$id])) {
		$user_egos[$id] = 0;
	}
}

require_once(dirname(__FILE__) . '/../lib/common.php');

$rows = db_query("SELECT u.id AS id, s.message AS message
		FROM shouts s
			JOIN users u ON (s.user = u.id)
		WHERE s.deleted = 0
			AND s.message LIKE '%ego%'
		ORDER BY s.id ASC");
$user_egos = array();
foreach($rows as $row) {
	if(preg_match_all('/ego\s*\+\+/', $row['message'], $matches, PREG_SET_ORDER)) {
		init_ego($user_egos, $row['id']);
		$user_egos[$row['id']] += count($matches);
	}
	if(preg_match_all('/ego\s*\-\-/', $row['message'], $matches, PREG_SET_ORDER)) {
		init_ego($user_egos, $row['id']);
		$user_egos[$row['id']] -= count($matches);
	}
	if(preg_match_all('/ego\s*\+=\s*([0-9]+)/', $row['message'], $matches, PREG_SET_ORDER)) {
		init_ego($user_egos, $row['id']);
		foreach($matches as $match) {
			$user_egos[$row['id']] += $match[1];
		}
	}
	if(preg_match_all('/ego\s*\-=\s*([0-9]+)/', $row['message'], $matches, PREG_SET_ORDER)) {
		init_ego($user_egos, $row['id']);
		foreach($matches as $match) {
			$user_egos[$row['id']] -= $match[1];
		}
	}
}
// $user_egos = array_filter($user_egos, function($a) { return $a != 0; });
arsort($user_egos);

$data = db_query('SELECT u.id AS id, u.name AS name, c.color AS color
		FROM users u
			JOIN user_categories c ON (u.category = c.id)');
$users = array();
foreach($data as $row) {
	if($row['color'] == '-') {
		$row['color'] = 'user';
	}
	$users[$row['id']] = $row;
}

ob_start();
require_once(dirname(__FILE__) . '/../templates/pages/ego.php');
$data = ob_get_contents();
ob_clean();

if(!$ajax) {
	xml_validate($data);
}
ob_start("ob_gzhandler");
echo $data;

log_data();

