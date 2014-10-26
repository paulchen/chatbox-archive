<?php
// when invoked via browser, do nothing
if(!defined('STDIN') && !defined($argc)) {
	die();
}

if($argc != 2) {
	die();
}

require_once('lib/common.php');

$max_id = get_setting('max_shout_id');
$epoch = get_setting('current_epoch');

$max_id_previous_epoch = 0;
$data = db_query('SELECT MAX(id) id FROM shouts WHERE epoch = ?', array($epoch - 1));
if(count($data) == 1) {
	$max_id_previous_epoch = $data[0]['id'];
}

$contents = file_get_contents($argv[1]);
if(strpos($contents, 'vsa_chatbox_bit') !== false) {
	$ret = process_chatbox($contents);
}
else if(strpos($contents, 'vsa_chatbox_archive_bit') !== false) {
	$ret = process_chatbox_archive($contents);
}
else {
	die();
}

set_setting('max_shout_id', $max_id);

$data = db_query('SELECT COUNT(*) shout_count FROM shouts');
set_setting('total_shouts', $data[0]['shout_count']);

$data = db_query('SELECT COUNT(*) shout_count FROM shouts WHERE deleted = 0');
set_setting('visible_shouts', $data[0]['shout_count']);

touch($success_file);

die($ret);

function process_nick_color($nick_color) {
	$query = 'SELECT id FROM user_categories WHERE color = ?';
	$data = db_query($query, array($nick_color));

	if(count($data) == 0) {
		$query = 'INSERT INTO user_categories (name, color) VALUES (?, ?)';
		db_query($query, array($nick_color, $nick_color));

		$query = 'SELECT id FROM user_categories WHERE color = ?';
		$data = db_query($query, array($nick_color));
	}

	return $data[0]['id'];
}

function process_nick($member_id, $member_nick, $nick_color) {
	$category = process_nick_color($nick_color);

	$query = 'SELECT id, name, category FROM users WHERE id = ?';
	$data = db_query($query, array($member_id));

	if(count($data) > 0) {
		if($member_nick != $data[0]['name'] || $category != $data[0]['category']) {
			$query = 'UPDATE users SET name = ?, category = ? WHERE id = ?';
			db_query($query, array($member_nick, $category, $member_id));
		}
	}
	else {
		$query = 'INSERT INTO users (id, name, category) VALUES (?, ?, ?)';
		db_query($query, array($member_id, $member_nick, $category));
	}
}

function process_shout($id, $date, $member_id, $member_nick, $nick_color, $message) {
	global $mysqli, $max_id, $epoch, $max_id_previous_epoch;

	process_nick($member_id, $member_nick, $nick_color);

	$query = 'SELECT primary_id, id, epoch, user_id, EXTRACT(EPOCH FROM "date") "date", message FROM shouts WHERE id = ? AND epoch = ?';
	$data = db_query($query, array($id, $epoch));

	$max_id = max($id, $max_id);
	if($id < $max_id - 10000) {
		$epoch++;
		set_setting('current_epoch', $epoch);
		$max_id = $id;
		$found = false;
		epoch_change_mail();
	}

	// TODO fix this bullshit here
	$temp_date = date('Y-m-d\TH:i:00.00', $date);
	$datetime = new DateTime($temp_date, new DateTimeZone('Etc/UTC'));

	$datetime->setTimezone((new DateTime())->getTimezone());
	$hour = $datetime->format('H');
	$day = $datetime->format('d');
	$month = $datetime->format('m');
	$year = $datetime->format('Y');

	$datetime->setTimezone(new DateTimeZone('Europe/London'));
	$date += $datetime->format('Z');

	if(count($data) == 0) {
		$primary_id = $id + $max_id_previous_epoch;

		$query = 'INSERT INTO shouts (primary_id, id, epoch, date, user_id, message, hour, day, month, year) VALUES (?, ?, ?, TO_TIMESTAMP(?), ?, ?, ?, ?, ?, ?)';
		db_query($query, array($primary_id, $id, $epoch, $date, $member_id, $message, $hour, $day, $month, $year));

		process_smilies($id, $epoch);
		process_words($id, $epoch);
		return 1;
	}

	$primary_id = $data[0]['primary_id'];
	$old_message = $data[0]['message'];
	$old_user = $data[0]['user_id'];
	$old_date = $data[0]['date']-7200; // TODO bullshit
	if($old_message == $message && $old_user == $member_id && $old_date == $date) {
		return 0;
	}

	if($old_message != $message) {
		echo "Shout $id: Message differs from old revision; old: $old_message; new: $message\n";
	}
	if($old_user != $member_id) {
		echo "Shout $id: Member ID differs from old revision; old: $old_user; new: $member_id\n";
	}
	if($old_date != $date) {
		echo "Shout $id: Date differs from old revision; old: $old_date; new: $date\n";
	}

	$query = 'SELECT MAX(revision) revision FROM shout_revisions WHERE id = ? AND epoch = ?';
	$data = db_query($query, array($id, $epoch));
	$revision = $data[0]['revision'] + 1;
	
	$replaced = time()-3600;

	$query = 'INSERT INTO shout_revisions (primary_id, id, epoch, revision, user_id, "date", replaced, text) VALUES (?, ?, ?, ?, ?, TO_TIMESTAMP(?), FROM_UNIXTIME(?), ?)';
	db_query($query, array($primary_id, $id, $epoch, $revision, $old_user, $old_date, $replaced, $old_message));

	$query = 'UPDATE shouts SET "date" = TO_TIMESTAMP(?), user_id = ?, message = ?, hour = ?, day = ?, month = ?, year = ? WHERE id = ? AND epoch = ?';
	db_query($query, array($date, $member_id, $message, $hour, $day, $month, $year, $id, $epoch));

	process_smilies($id, $epoch);
	process_words($id, $epoch);
	
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
		$date_array = strptime($rawdate, '%d-%m-%y, %H:%M');

//		$year = gmdate('y');
//		if(gmdate('m') < $date_array['tm_mon']+1) {
//			$year++;
//		}
		$date = mktime($date_array['tm_hour'], $date_array['tm_min'], 0, $date_array['tm_mon']+1, $date_array['tm_mday'], $date_array['tm_year']+1900);

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
		$messagepos2 = strpos($shout, "\t\t</td>", $messagepos1)-1;
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
		$date_array = strptime($rawdate, '%d-%m-%y, %H:%M');

//		$year = gmdate('y');
		$date = mktime($date_array['tm_hour'], $date_array['tm_min'], 0, $date_array['tm_mon']+1, $date_array['tm_mday'], $date_array['tm_year']+1900);

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
	send_mail('epoch_change.php', 'Epoch change');
}

function message_count_error($expected, $actual) {
	global $argv;

	$parameters = array('INPUT_FILE' => $argv[1], 'EXPECTED' => $expected, 'ACTUAL' => $actual);
	$attachments = array($argv[1]);
	send_mail('message_count.php', 'Processing error', $parameters, true, $attachments);
}

