<?php
function check_user(&$users_data, $user_id) {
	if(!isset($users_data[$user_id])) {
		$users_data[$user_id] = array('bananas_added' => array(), 'bananas_annihilated' => array(), 'annihilation_times' => array());
	}
}

function annihilate_bananas(&$users_data, &$open_bananas, &$first_banana_time, $user_id, $timestamp, $shout_id) {
	check_user($users_data, $user_id);

	$annihilated_bananas = 0;
	foreach($open_bananas as $banana => $count) {
		if(!isset($users_data[$user_id]['bananas_annihilated'][$banana])) {
			$users_data[$user_id]['bananas_annihilated'][$banana] = 0;
		}
		$users_data[$user_id]['bananas_annihilated'][$banana] += $open_bananas[$banana];
		$annihilated_bananas += $open_bananas[$banana];

		$open_bananas[$banana] = 0;
	}
	if($annihilated_bananas > 0) {
		$users_data[$user_id]['annihilation_times'][] = max(60, $timestamp-$first_banana_time);
	}
}

function add_banana(&$users_data, &$open_bananas, &$first_banana_time, $banana, $user_id, $timestamp) {
	check_user($users_data, $user_id);

	$total_open_bananas = 0;
	foreach($open_bananas as $value) {
		$total_open_bananas += $value;
	}
	if($total_open_bananas == 0) {
		$first_banana_time = $timestamp;
	}

	if(!isset($open_bananas[$banana])) {
		$open_bananas[$banana] = 0;
	}
	$open_bananas[$banana]++;

	if(!isset($users_data[$user_id]['bananas_added'][$banana])) {
		$users_data[$user_id]['bananas_added'][$banana] = 0;
	}
	$users_data[$user_id]['bananas_added'][$banana]++;
}

function calculate_bananas($rows) {
	global $debug;

	$users_data = array();
	$open_bananas = array();
	$first_banana_time = 0;
	foreach($rows as $row) {
		if($debug) {
			echo "processing row with id '${row['shout_id']}'... ";
		}
		if(preg_match_all('+<[^<>]*/(NoDevilBanana|trampolindb|devil-banana|turbo-devil-banana|extreme-turbo-devil-banana)\.gif[^<>]*>+', $row['message'], $matches, PREG_SET_ORDER)) {
			foreach($matches as $match) {
				switch($match[0]) {
				case '<img src="pics/nb/smilies/NoDevilBanana.gif" border="0" alt="" title="No Devil Banana" class="inlineimg" />':
					annihilate_bananas($users_data, $open_bananas, $first_banana_time, $row['id'], $row['timestamp'], $row['shout_id']);
					break;

				case '<img src="images/smilies/trampolindb.gif" border="0" alt="" title="devil banana jumping on trampoline" class="inlineimg" />':
				case '<img src="pics/nb/smilies/devil-banana.gif" border="0" alt="" title="devil banana" class="inlineimg" />':
				case '<img src="images/smilies/turbo-devil-banana.gif" border="0" alt="" title="turbo devil banana" class="inlineimg" />':
				case '<img src="images/smilies/extreme-turbo-devil-banana.gif" border="0" alt="" title="extreme turbo devil banana" class="inlineimg" />':
					add_banana($users_data, $open_bananas, $first_banana_time, $match[1], $row['id'], $row['timestamp']);
					break;

				default:
					/* nothing to do */
				}
			}
		}
		if($debug) {
			echo "ego now available: $available_ego\n";
		}
	}

	$total = array('total_added' => 0, 'total_annihilated' => 0);
	$total_min_time = -60;
	$total_max_time = -60;
	$total_total_time = 0;
	$total_annihilations = 0;
	$total_annihilated = 0;
	$total_added = 0;
	foreach($users_data as &$user) {
		$added = 0;
		foreach($user['bananas_added'] as $value) {
			$added += $value;
		}
		$user['total_added'] = $added;
		$total['total_added'] += $added;

		$annihilated = 0;
		foreach($user['bananas_annihilated'] as $value) {
			$annihilated += $value;
		}
		$user['total_annihilated'] = $annihilated;
		$total['total_annihilated'] += $annihilated;

		$min_time = -60;
		$max_time = -60;
		$avg_time = -60;
		$total_time = 0;
		foreach($user['annihilation_times'] as $time) {
			if($min_time == -60 || $time < $min_time) {
				$min_time = $time;
			}
			if($max_time < $time) {
				$max_time = $time;
			}
			$total_time += $time;
		}
		if($total_time > 0) {
			$avg_time = $total_time/count($user['annihilation_times']);
		}
		$user['times'] = array('min' => round($min_time/60), 'max' => round($max_time/60), 'avg' => round($avg_time/60));

		foreach(array('bananas_added', 'bananas_annihilated') as $item) {
			foreach($user[$item] as $banana => $value) {
				if(!isset($total[$item][$banana])) {
					$total[$item][$banana] = 0;
				}
				$total[$item][$banana] += $value;
			}
		}

		$total_annihilations += count($user['annihilation_times']);
		if($total_min_time == -60 || ($min_time > 0 && $min_time < $total_min_time)) {
			$total_min_time = $min_time;
		}
		if($max_time > $total_max_time) {
			$total_max_time = $max_time;
		}
		$total_total_time += $total_time;
	}
	unset($user);
	if($total_annihilations > 0) {
		$total_avg_time = $total_total_time/$total_annihilations;
	}
	else {
		$total_avg_time = -60;
	}
	$total['times'] = array('min' => round($total_min_time/60), 'max' => round($total_max_time/60), 'avg' => round($total_avg_time/60));

	uasort($users_data, function($a, $b) {
		if($a['total_added'] == $b['total_added']) {
			if($a['total_annihilated'] == $b['total_annihilated']) {
				return 0;
			}
			return ($a['total_annihilated'] < $b['total_annihilated']) ? 1 : -1;
		}
		return ($a['total_added'] < $b['total_added']) ? 1 : -1;
	});

	if($debug) {
		die();
	}

	return array('user_bananas' => $users_data, 'total' => $total);
}


