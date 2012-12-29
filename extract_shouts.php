<?php
// when invoked via browser, do nothing
if(!defined('STDIN') && !defined($argc)) {
	die();
}

if($argc != 2) {
	die();
}

require_once('config.php');

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
$mysqli->query('SET NAMES utf8');
$mysqli->query('LOCK TABLES shouts WRITE, users WRITE, user_categories WRITE');

$processed_ids = array();

$contents = file_get_contents($argv[1]);
if(strpos($contents, 'vsa_chatbox_bit') !== false) {
	$ret = process_chatbox($contents);
}
else if(strpos($contents, 'vsa_chatbox_archive_bit') !== false) {
	$ret = process_chatbox_archive($contents);
}
else {
	$mysqli->query('UNLOCK TABLES');
	die();
}

sort($processed_ids);
$min = $processed_ids[0];
$max = $processed_ids[count($processed_ids)-1];

$stmt = $mysqli->prepare('SELECT id FROM shouts WHERE id >= ? AND id <= ?');
$stmt->bind_param('ii', $min, $max);
$stmt->execute();
$stmt->bind_result($id);
$deleted_ids = array();
while($stmt->fetch()) {
	if(!in_array($id, $processed_ids)) {
		$deleted_ids[] = $id;
	}
}
$stmt->close();

/*
$stmt = $mysqli->prepare('UPDATE shouts SET deleted = 1 WHERE id = ?');
foreach($deleted_ids as $id) {
	$stmt->bind_param('i', $id);
	$stmt->execute();
}
$stmt->close();
 */

$mysqli->query('UNLOCK TABLES');

$mysqli->close();
die($ret);

function process_nick_color($nick_color) {
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT id FROM user_categories WHERE color = ?');
	$stmt->bind_param('s', $nick_color);
	$stmt->execute();
	$stmt->bind_result($id);
	$found = false;
	while($stmt->fetch()) {
		$found = true;
	}
	$stmt->close();

	if(!$found) {
		$stmt = $mysqli->prepare('INSERT INTO user_categories (name, color) VALUES (?, ?)');
		$stmt->bind_param('ss', $nick_color, $nick_color);
		$stmt->execute();
		$id = $mysqli->insert_id;
		$stmt->close();
	}

	return $id;
}

function process_nick($member_id, $member_nick, $nick_color) {
	global $mysqli;

	$id = process_nick_color($nick_color);

	$stmt = $mysqli->prepare('SELECT id FROM users WHERE id = ?');
	$stmt->bind_param('i', $member_id);
	$stmt->execute();
	$stmt->bind_result($found_id);
	$found = false;
	while($stmt->fetch()) {
		$found = true;
	}
	$stmt->close();

	if($found) {
		$stmt = $mysqli->prepare('UPDATE users SET name = ?, category = ? WHERE id = ?');
		$stmt->bind_param('sii', $member_nick, $id, $member_id);
		$stmt->execute();
		$stmt->close();
	}
	else {
		$stmt = $mysqli->prepare('INSERT INTO users (id, name, category) VALUES (?, ?, ?)');
		$stmt->bind_param('isi', $member_id, $member_nick, $id);
		$stmt->execute();
		$stmt->close();
	}
}

function process_shout($id, $date, $member_id, $member_nick, $nick_color, $message) {
	global $mysqli, $processed_ids;

	$processed_ids[] = $id;

	process_nick($member_id, $member_nick, $nick_color);

	$stmt = $mysqli->prepare('SELECT id FROM shouts WHERE id = ?');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$stmt->bind_result($fetched_id);
	$found = false;
	while($stmt->fetch()) {
		$found = true;
	}
	$stmt->close();

	if(!$found) {
		$stmt = $mysqli->prepare('INSERT INTO shouts (id, date, user, message) VALUES (?, FROM_UNIXTIME(?), ?, ?)');
		$stmt->bind_param('iiis', $id, $date, $member_id, $message);
		$stmt->execute();
		$stmt->close();

		return 1;
	}

	return 0;
}

function process_chatbox($contents) {
	$last_pos = 0;
	$ret = 0;
	while(true) {
		$pos1 = strpos($contents, '<!-- BEGIN TEMPLATE: vsa_chatbox_bit -->', $last_pos);
		if($pos1 === false) {
			break;
		}
		$pos2 = strpos($contents, '<!-- END TEMPLATE: vsa_chatbox_bit -->', $pos1);
		if($pos2 === false) {
			break;
		}
		$shout = substr($contents, $pos1, $pos2);

		$idpos1 = strpos($shout, 'ccbloc=')+7;
		$idpos2 = strpos($shout, '"', $idpos1);
		$id = substr($shout, $idpos1, $idpos2-$idpos1);

		$datepos1 = strpos($shout, '[');
		$datepos2 = strpos($shout, ']');
		$rawdate = substr($shout, $datepos1+1, $datepos2-$datepos1-1);
		$date_array = strptime($rawdate, '%d-%m, %H:%M');

		$year = date('y');
		if(date('m') < $date_array['tm_mon']+1) {
			$year++;
		}
		$date = mktime($date_array['tm_hour'], $date_array['tm_min'], 0, $date_array['tm_mon']+1, $date_array['tm_mday'], $year);

		$memberpos1 = strpos($shout, 'member.php?u=')+13;
		$memberpos2 = strpos($shout, '"', $memberpos1);
		$member_id = substr($shout, $memberpos1, $memberpos2-$memberpos1);

		$memberpos3 = strpos($shout, '</a>', $memberpos2);
		$member_nick_raw = substr($shout, $memberpos2+2, $memberpos3-$memberpos2-2);
		$member_nick = $member_nick_raw;

		$nick_color = '-';
		if(($memberpos4 = strpos($member_nick_raw, '<Font Color=')) !== false) {
			$memberpos5 = strpos($member_nick_raw, '>', $memberpos4);
			$memberpos6 = strpos($member_nick_raw, '<', $memberpos5);
			$member_nick = substr($member_nick_raw, $memberpos5+1, $memberpos6-$memberpos5-1);

			$memberpos7 = strpos($member_nick_raw, '"', $memberpos4+13);
			$nick_color = substr($member_nick_raw, $memberpos4+13, $memberpos7-$memberpos4-13);
		}

		$messagepos1 = strpos($shout, '<td style="font-size:;vertical-align:bottom;">')+50;
		$messagepos2 = strpos($shout, '</td>', $messagepos1)-3;
		$message = substr($shout, $messagepos1, $messagepos2-$messagepos1);

		$ret += process_shout($id, $date, $member_id, $member_nick, $nick_color, $message);

		$last_pos = $pos2;
	}

	return $ret;
}

function process_chatbox_archive($contents) {
	$last_pos = 0;
	$ret = 0;
	while(true) {
		$pos1 = strpos($contents, '<!-- BEGIN TEMPLATE: vsa_chatbox_archive_bit -->', $last_pos);
		if($pos1 === false) {
			break;
		}
		// $pos1 += $last_pos;
		$pos2 = strpos($contents, '<!-- END TEMPLATE: vsa_chatbox_archive_bit -->', $pos1);
		if($pos2 === false) {
			break;
		}
		$shout = substr($contents, $pos1, $pos2-$pos1);

		$idpos1 = strpos($shout, '<a name="')+9;
		$idpos2 = strpos($shout, '"', $idpos1);
		$id = substr($shout, $idpos1, $idpos2-$idpos1);

		$datepos1 = strpos($shout, '  ', $idpos2);
		$datepos2 = strpos($shout, '</td>', $datepos1);
		$rawdate = trim(substr($shout, $datepos1+1, $datepos2-$datepos1-1));
		$date_array = strptime($rawdate, '%d-%m, %H:%M');

		// TODO year issues
		$year = 2012;
		$date = mktime($date_array['tm_hour'], $date_array['tm_min'], 0, $date_array['tm_mon']+1, $date_array['tm_mday'], $year);

		$memberpos1 = strpos($shout, 'member.php?u=')+13;
		$memberpos2 = strpos($shout, '"', $memberpos1);
		$member_id = substr($shout, $memberpos1, $memberpos2-$memberpos1);

		$memberpos2x = strpos($shout, 'class="popupctrl">');
		$memberpos3 = strpos($shout, '</a>', $memberpos2x);
		$member_nick_raw = substr($shout, $memberpos2x+18, $memberpos3-$memberpos2x-18);
		$member_nick = $member_nick_raw;

		$nick_color = '-';
		if(($memberpos4 = strpos($member_nick_raw, '<Font Color=')) !== false) {
			$memberpos5 = strpos($member_nick_raw, '>', $memberpos4);
			$memberpos6 = strpos($member_nick_raw, '<', $memberpos5);
			$member_nick = substr($member_nick_raw, $memberpos5+1, $memberpos6-$memberpos5-1);

			$memberpos7 = strpos($member_nick_raw, '"', $memberpos4+13);
			$nick_color = substr($member_nick_raw, $memberpos4+13, $memberpos7-$memberpos4-13);
		}

		$messagepos1x = strpos($shout, 'vsacb_message');
		$messagepos1 = strpos($shout, '>', $messagepos1x)+9;
		$messagepos2 = strpos($shout, "\t\t\t\t\t</div>", $messagepos1)-2;
		$message = substr($shout, $messagepos1, $messagepos2-$messagepos1);

		$ret += process_shout($id, $date, $member_id, $member_nick, $nick_color, $message);

		$last_pos = $pos2;
	}

	return $ret;
}

