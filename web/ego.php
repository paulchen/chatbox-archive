<?php

function increase_ego(&$user_egos, &$available_ego, $user_id, $count) {
	$increment = min($available_ego, $count);
	if($increment > 0) {
		$available_ego -= $increment;
	}
	if(!isset($user_egos[$user_id])) {
		$user_egos[$user_id] = 0;
	}
	$user_egos[$user_id] += $increment;
}

require_once(dirname(__FILE__) . '/../lib/common.php');

$rows = db_query("SELECT u.id AS id, s.message AS message
		FROM shouts s
			JOIN users u ON (s.user = u.id)
		WHERE s.deleted = 0
			AND (s.message LIKE '%ego%' OR s.message LIKE '%/hail.gif%' OR s.message LIKE '%/multihail.gif%')
		ORDER BY s.id ASC");
$user_egos = array();
$available_ego = 0;
foreach($rows as $row) {
	if(preg_match_all('+/((multi)?hail)\.gif+', $row['message'], $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if($match[1] == 'multihail') {
				$available_ego += 16;
			}
			else if($match[1] == 'hail') {
				$available_ego++;
			}
		}
	}
	if(preg_match_all('/ego\s*(\+\+|\-\-|(\+|\-)=\s*([0-9]+))/', $row['message'], $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if($match[1] == '++') {
				increase_ego($user_egos, $available_ego, $row['id'], 1);
			}
			else if($match[1] == '--') {
				increase_ego($user_egos, $available_ego, $row['id'], -1);
			}
			else if($match[2] == '+') {
				increase_ego($user_egos, $available_ego, $row['id'], $match[3]);
			}
			else if($match[2] == '-') {
				increase_ego($user_egos, $available_ego, $row['id'], -$match[3]);
			}
		}
	}
}
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

xml_validate($data);
ob_start("ob_gzhandler");
echo $data;

log_data();

