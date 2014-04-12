<?php

function increase_ego(&$user_egos, &$available_ego, &$available_ego_per_person, $user_id, $count) {
	global $debug;

	if(!isset($available_ego_per_person[$user_id])) {
		$available_ego_per_person[$user_id] = 0;
	}
	$increment = min($available_ego-$available_ego_per_person[$user_id], $count);
	if($increment < -1000) {
		$increment = -1000;
	}
	if($increment > 0) {
		if($debug) {
			echo "incrementing ego of user $user_id by $increment... ";
		}
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
	global $debug;

	if($debug) {
		echo "making $ego ego available... ";
	}

	$available_ego += $ego;
	if(!isset($available_ego_per_person[$user_id])) {
		$available_ego_per_person[$user_id] = 0;
	}
	$available_ego_per_person[$user_id] += $ego;
}

function destroy_available_ego(&$available_ego, &$available_ego_per_person, $ego) {
	global $debug;

	$decrement = min($ego, $available_ego);
	if($debug) {
		echo "removing $decrement ego... ";
	}
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

function calculate_ego($rows) {
	global $debug;

	$user_egos = array();
	$available_ego = 0;
	$available_ego_per_person = array();
	foreach($rows as $row) {
		if($debug) {
			echo "processing row with id '${row['shout_id']}'... \n";
		}
		if(preg_match_all('+<[^<>]*/(multi|anti)?hail(db)?\.(gif|png)[^<>]*>+', $row['message'], $matches, PREG_SET_ORDER)) {
			foreach($matches as $match) {
				if($match[0] == '<img src="images/ob/smilies/multihail.gif" border="0" alt="" title="multihail" class="inlineimg" />') {
					make_ego_available($available_ego, $available_ego_per_person, $row['id'], 16);
				}
				else if($match[0] == '<img src="images/smilies/multihaildb.gif" border="0" alt="" title="hail the devil banana" class="inlineimg" />') {
					make_ego_available($available_ego, $available_ego_per_person, $row['id'], 16);
				}
				else if($match[0] == '<img src="images/ob/smilies/antihail.png" border="0" alt="" title=":nohail:" class="inlineimg" />') {
					destroy_available_ego($available_ego, $available_ego_per_person, 1);
				}
				else if($match[0] == '<img src="pics/nb/smilies/hail.gif" border="0" alt="" title="hail" class="inlineimg" />') {
					make_ego_available($available_ego, $available_ego_per_person, $row['id'], 1);
				}
//				else if($match[1] == 'multihail' || $match[1] == 'antihail' || $match[1] == 'hail') {
//					echo "!" . $match[1] . " " . $row['id'] . "\n";
//				}
			}
		}
//		else if(preg_match_all('+/((multi|anti)?hail)\.(gif|png)+', $row['message'], $matches, PREG_SET_ORDER)) {
//			foreach($matches as $match) {
//				echo 'x' . $match[1] . " " . $row['id'] . "\n";
//			}
//		}
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
		if($debug) {
			echo "ego now available: $available_ego\n";
		}
	}
	arsort($user_egos);

	if($debug) {
		die();
	}

	return array('user_egos' => $user_egos, 'available_ego' => $available_ego);
}


