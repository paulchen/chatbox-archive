<?php

function increase_ego(&$user_egos, &$available_ego, &$available_ego_per_person, $user_id, $count) {
	if(!isset($available_ego_per_person[$user_id])) {
		$available_ego_per_person[$user_id] = 0;
	}
	$increment = min($available_ego-$available_ego_per_person[$user_id], $count);
	if($increment < -1000) {
		$increment = -1000;
	}
	if($increment > 0) {
		$available_ego -= $increment;
		ksort($available_ego_per_person);
		$to_subtract = $increment;
		foreach($available_ego_per_person as $key => $value) {
			if($key == $user_id) {
				continue;
			}
			$egos = min($to_subtract, $value);
			$available_ego_per_person[$key] = $value-$egos;
			$to_subtract -= $egos;
			if($to_subtract == 0) {
				break;
			}
		}
	}
	if(!isset($user_egos[$user_id])) {
		$user_egos[$user_id] = 0;
	}
	$user_egos[$user_id] += $increment;
}

function make_ego_available(&$available_ego, &$available_ego_per_person, $user_id, $ego) {
	$available_ego += $ego;
	if(!isset($available_ego_per_person[$user_id])) {
		$available_ego_per_person[$user_id] = 0;
	}
	$available_ego_per_person[$user_id] += $ego;
}

function destroy_available_ego(&$available_ego, &$available_ego_per_person, $ego) {
	$decrement = min($ego, $available_ego);
	$available_ego -= $decrement;
	$to_subtract = $decrement;
	ksort($available_ego_per_person);
	foreach($available_ego_per_person as $key => $value) {
		if($value > 0) {
			$person_decrement = max($value, $to_subtract);
			$to_subtract -= $person_decrement;
			$available_ego_per_person[$key] = $value-$person_decrement;
		}
		if($to_subtract == 0) {
			break;
		}
	}
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
$available_ego_per_person = array();
foreach($rows as $row) {
	if(preg_match_all('+/((multi|anti)?hail)\.(gif|png)+', $row['message'], $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if($match[1] == 'multihail') {
				make_ego_available($available_ego, $available_ego_per_person, $row['id'], 16);
			}
			else if($match[1] == 'antihail') {
				destroy_available_ego($available_ego, $available_ego_per_person, 1);
			}
			else if($match[1] == 'hail') {
				make_ego_available($available_ego, $available_ego_per_person, $row['id'], 1);
			}
		}
	}
	if(preg_match_all('/ego\s*(\+\+|\-\-|(\+|\-)=\s*([0-9]+))/', $row['message'], $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if($match[1] == '++') {
				increase_ego($user_egos, $available_ego, $available_ego_per_person, $row['id'], 1);
			}
			else if($match[1] == '--') {
				increase_ego($user_egos, $available_ego, $available_ego_per_person, $row['id'], -1);
			}
			else if($match[2] == '+') {
				increase_ego($user_egos, $available_ego, $available_ego_per_person, $row['id'], $match[3]);
			}
			else if($match[2] == '-') {
				increase_ego($user_egos, $available_ego, $available_ego_per_person, $row['id'], -$match[3]);
			}
		}
	}
	if(preg_match_all('/ego\s*=\s*ego\s*(\+|\-)\s*([0-9]+)/', $row['message'], $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if($match[1] == '+') {
				increase_ego($user_egos, $available_ego, $available_ego_per_person, $row['id'], $match[2]);
			}
			if($match[1] == '-') {
				increase_ego($user_egos, $available_ego, $available_ego_per_person, $row['id'], -$match[2]);
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

