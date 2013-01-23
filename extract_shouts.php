<?php
// when invoked via browser, do nothing
if(!defined('STDIN') && !defined($argc)) {
	die();
}

if($argc != 2) {
	die();
}

require_once('lib/common.php');

db_query('LOCK TABLES shouts WRITE, users WRITE, user_categories WRITE, settings WRITE');

$max_id = get_setting('max_shout_id');
$epoch = get_setting('current_epoch');

$processed_ids = array();

$contents = file_get_contents($argv[1]);
if(strpos($contents, 'vsa_chatbox_bit') !== false) {
	$ret = process_chatbox($contents);
}
else if(strpos($contents, 'vsa_chatbox_archive_bit') !== false) {
	$ret = process_chatbox_archive($contents);
}
else {
	db_query('UNLOCK TABLES');
	die();
}

set_setting('max_shout_id', $max_id);

sort($processed_ids);
$min = $processed_ids[0];
$max = $processed_ids[count($processed_ids)-1];

$query = 'SELECT id FROM shouts WHERE id >= ? AND id <= ?';
$data = db_query($query, array($min, $max));
$deleted_ids = array();
foreach($data as $row) {
	if(!in_array($row['id'], $processed_ids)) {
		$deleted_ids[] = $row['id'];
	}
}

/*
$query = ('UPDATE shouts SET deleted = 1 WHERE id = ?';
foreach($deleted_ids as $id) {
	db_query($query, array($id));
}
 */

db_query('UNLOCK TABLES');

die($ret);

function process_nick_color($nick_color) {
	$query = 'SELECT id FROM user_categories WHERE color = ?';
	$data = db_query($query, array($nick_color));

	if(count($data) == 0) {
		$query = 'INSERT INTO user_categories (name, color) VALUES (?, ?)';
		db_query($query, array($nick_color, $nick_color));
	}

	$query = 'SELECT id FROM user_categories WHERE color = ?';
	$data = db_query($query, array($nick_color));

	return $data[0]['id'];
}

function process_nick($member_id, $member_nick, $nick_color) {
	$id = process_nick_color($nick_color);

	$query = 'SELECT id FROM users WHERE id = ?';
	$data = db_query($query, array($member_id));

	if(count($data) > 0) {
		$query = 'UPDATE users SET name = ?, category = ? WHERE id = ?';
		db_query($query, array($member_nick, $id, $member_id));
	}
	else {
		$query = 'INSERT INTO users (id, name, category) VALUES (?, ?, ?)';
		db_query($query, array($member_id, $member_nick, $id));
	}
}

function process_shout($id, $date, $member_id, $member_nick, $nick_color, $message) {
	global $mysqli, $processed_ids, $max_id, $epoch;

	$processed_ids[] = $id;

	process_nick($member_id, $member_nick, $nick_color);

	$query = 'SELECT id, epoch FROM shouts WHERE id = ? AND epoch = ?';
	$data = db_query($query, array($id, $epoch));

	$max_id = max($id, $max_id);
	// TODO magic number
	/*
	if($id < $max_id - 10000) {
		$epoch++;
		set_setting('current_epoch', $epoch);
		$max_id = $id;
		$found = false;
		epoch_change_mail();
	}
	 */

	if(count($data) == 0) {
		$query = 'INSERT INTO shouts (id, epoch, date, user, message) VALUES (?, ?, FROM_UNIXTIME(?), ?, ?)';
		db_query($query, array($id, $epoch, $date, $member_id, $message));

		return 1;
	}

	$query = 'UPDATE shouts SET user = ?, message = ? WHERE id = ? AND epoch = ?';
	db_query($query, array($member_id, $message, $id, $epoch));

	return 0;
}

function process_chatbox($contents) {
	$last_pos = 0;
	$ret = 0;
	$processed = 0;
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

		$year = gmdate('y');
		if(gmdate('m') < $date_array['tm_mon']+1) {
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

		$processed++;
	}

	// TODO magic number
	if($processed != 30) {
		message_count_error(30, $processed);
	}

	return $ret;
}

function process_chatbox_archive($contents) {
	$last_pos = 0;
	$ret = 0;
	$processed = 0;
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

		$processed++;
	}

	// TODO magic number
	if($processed != 25) {
		message_count_error(25, $processed);
	}

	return $ret;
}

function epoch_change_mail() {
	ob_start();
	require(dirname(__FILE__) . '/templates/mails/epoch_change.php');
	$message = ob_get_contents();
	ob_end_clean();

	// TODO duplicate code
	$headers = "From: $email_from\n";
	$headers .= "Content-Type: text/plain; charset = \"UTF-8\";\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";

	$subject = 'Processing error';

	mail($report_email, $subject, $message, $headers);

	die();
}

function message_count_error($expected, $actual) {
	global $argv, $email_from, $report_email;

	$input_file = $argv[1];
	ob_start();
	require(dirname(__FILE__) . '/templates/mails/message_count.php');
	$message = ob_get_contents();
	ob_end_clean();

	// TODO duplicate code
	$headers = "From: $email_from\n";
	$headers .= "Content-Type: text/plain; charset = \"UTF-8\";\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";

	$subject = 'Processing error';

	mail($report_email, $subject, $message, $headers);

	die();
}

