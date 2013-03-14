<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

function build_link_from_request() {
	$keys = func_get_args();
	$link_parts = '';
	foreach($keys as $key) {
		if(isset($_REQUEST[$key])) {
			$link_parts .= "&amp;$key=" . urlencode($_REQUEST[$key]);
		}
	}
	return $link_parts;
}

function overview_redirect() {
	header('Location: overview.php');
	die();
}

function add_user_link(&$row) {
	// TODO simplify this
	$link_parts = build_link_from_request('day', 'month', 'year', 'hour', 'smiley', 'period', 'word');

	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . $link_parts . '">' . $row[0]['name'] . '</a>';
}

function messages_per_hour(&$row) {
	$link_parts = build_link_from_request('day', 'month', 'year', 'user', 'smiley', 'period', 'word');

	$row[0]['hour'] = '<a href="details.php?hour=' . $row[0]['hour'] . $link_parts . '">' . $row[0]['hour'] . '</a>';
	spammer_smiley($row);
}

function messages_per_month(&$row) {
	$link_parts = build_link_from_request('user', 'hour', 'smiley', 'period', 'word');

	$parts = explode('-', $row[0]['monthx']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['monthx'] = "<a href=\"details.php?month=$month&amp;year=$year$link_parts\">" . $row[0]['monthx'] . '</a>';
	spammer_smiley($row);
}

function spammer_smiley(&$row) {
	if($row[0]['top_spammer'] != '') {
		$parts = explode('$$', $row[0]['top_spammer']);
		$row[0]['top_spammer'] = "<a href=\"details.php?user={$parts[1]}\">{$parts[1]}</a> ({$parts[2]}x)";
	}
	else {
		$row[0]['top_spammer'] = '-';
	}

	if($row[0]['popular_smiley'] != '') {
		$parts = explode('$$', $row[0]['popular_smiley']);
		$row[0]['popular_smiley'] = "<a href=\"details.php?smiley={$parts[0]}\"><img src=\"images/smilies/{$parts[1]}\" alt=\"\" /></a> ({$parts[2]}x)";
	}
	else {
		$row[0]['popular_smiley'] = '-';
	}
}

function messages_per_year(&$row) {
	$link_parts = build_link_from_request('user', 'hour', 'smiley', 'period', 'word');

	$row[0]['yearx'] = "<a href=\"details.php?year=" . $row[0]['yearx'] . "$link_parts\">" . $row[0]['yearx'] . '</a>';
	spammer_smiley($row);
}

if(!isset($_REQUEST['user']) && !isset($_REQUEST['year']) && !isset($_REQUEST['hour']) && !isset($_REQUEST['smiley']) && !isset($_REQUEST['period']) && !isset($_REQUEST['word'])) {
	overview_redirect();
}
if(isset($_REQUEST['day']) && !isset($_REQUEST['month'])) {
	overview_redirect();
}
if(isset($_REQUEST['month']) && !isset($_REQUEST['year'])) {
	overview_redirect();
}

if(isset($_REQUEST['user'])) {
	$user = $_REQUEST['user'];

	$user_data = db_query('SELECT id FROM users WHERE name = ?', array($user));
	if(count($user_data) != 1) {
		overview_redirect();
	}
	$user_id = $user_data[0]['id'];
}
if(isset($_REQUEST['smiley'])) {
	$smiley_id = $_REQUEST['smiley'];

	$smiley_data = db_query('SELECT filename FROM smilies WHERE id = ?', array($smiley_id));
	if(count($smiley_data) != 1) {
		overview_redirect();
	}
	$smiley_filename = $smiley_data[0]['filename'];
}
if(isset($_REQUEST['word'])) {
	$word = $_REQUEST['word'];

	$word_data = db_query('SELECT id FROM words WHERE word = ?', array($word));
	if(count($word_data) != 1) {
		overview_redirect();
	}
	$word_id = $word_data[0]['id'];
}
if(isset($_REQUEST['year'])) {
	if(isset($_REQUEST['day'])) {
		$date_format = '%Y-%m-%d';
		$date = sprintf('%04d-%02d-%02d', $_REQUEST['year'], $_REQUEST['month'], $_REQUEST['day']);
	}
	else if(isset($_REQUEST['month'])) {
		$date_format = '%Y-%m';
		$date = sprintf('%04d-%02d', $_REQUEST['year'], $_REQUEST['month']);
	}
	else {
		$date_format = '%Y';
		$date = sprintf('%04d', $_REQUEST['year']);
	}
}
if(isset($_REQUEST['hour'])) {
	$hour = $_REQUEST['hour'];
}

$filter_parts = array();
$params = array();
$what_parts = array();

if(isset($_REQUEST['hour'])) {
	$filter_parts[] = "lpad((date_format(date, '%H')+1) % 24, 2, '0') = ?";
	$params[] = $hour;
	$what_parts[] = "hour $hour";
}
if(isset($_REQUEST['year'])) {
	$filter_parts[] = "date_format(date_add(date, interval 1 hour), '$date_format') = ?";
	$params[] = $date;
	$what_parts[] = $date;
}
if(isset($_REQUEST['user'])) {
	$filter_parts[] = "user = ?";
	$params[] = $user_id;
	$what_parts[] = $user;
}
if(isset($_REQUEST['smiley'])) {
	$filter_parts[] = "(s.id, s.epoch) in (select shout_id, shout_epoch from shout_smilies where smiley = ?)";
	$params[] = $smiley_id;
	$what_parts[] = "smiley <img src=\"images/smilies/$smiley_filename\" alt=\"\" />";
}
if(isset($_REQUEST['word'])) {
	$filter_parts[] = "(s.id, s.epoch) in (select shout_id, shout_epoch from shout_words where word = ?)";
	$params[] = $word_id;
	$what_parts[] = "word \"" . htmlentities($_REQUEST['word'], ENT_QUOTES, 'UTF-8') . "\"";
}
if(isset($_REQUEST['period'])) {
	// TODO improve this
	$last_archive_id = 229152;
	$last_archive_epoch = 1;

	$data = db_query('SELECT UNIX_TIMESTAMP(date) date FROM shouts WHERE (epoch = ? AND id >= ?) OR (epoch > ?) ORDER BY epoch ASC, id ASC LIMIT 0, 1', array($last_archive_epoch, $last_archive_id, $last_archive_epoch));
	$date_string = date('Y-m-d', $data[0]['date']);

	$filter_parts[] = '(s.epoch = ? AND s.id >= ?) OR (epoch > ?)';
	$params[] = $last_archive_epoch;
	$params[] = $last_archive_id;
	$params[] = $last_archive_epoch;
	$what_parts[] = "since $date_string";
}

$filter = implode(' AND ', $filter_parts);
$what = implode(', ', $what_parts);

$queries = array();
$queries[] = array(
		'title' => 'Top spammers',
		'query' => "select concat(@row:=@row+1, '.'), b.name, b.shouts, coalesce(b.shouts/ceil((b.last_shout-b.first_shout)/86400), 1) as average_shouts_per_day, b.smilies, b.smilies/b.shouts as average_smilies_per_message, b.smiley_info, b.word_info
			from (select a.name, a.shouts, a.smilies,
				(select unix_timestamp(min(date)) from shouts s where s.user=a.id and deleted=0 and $filter) as first_shout,
				(select unix_timestamp(max(date)) from shouts s where s.user=a.id and deleted=0 and $filter) as last_shout,
				(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count))
					from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id)
					where s.user = a.id and deleted = 0 and $filter
					group by ss.smiley, sm.filename
					order by sum(ss.count) desc
					limit 0, 1) as smiley_info,
				(select concat(sw.word, '$$', w.word, '$$', sum(sw.count))
					from shouts s join shout_words sw on (s.id = sw.shout_id and s.epoch = sw.shout_epoch) join words w on (sw.word = w.id)
					where s.user = a.id and deleted = 0 and $filter
					group by sw.word, w.word
					order by sum(sw.count) desc
					limit 0, 1) as word_info
				from (select u.id, u.name, count(distinct s.id) as shouts, coalesce(sum(ss.count), 0) as smilies from shouts s join users u
				on (s.user = u.id) left join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch)
				where deleted = 0 and $filter group by u.id, u.name) a) b, (select @row:=0) c
			order by b.shouts desc, average_shouts_per_day desc, b.name asc",
		'params' => array_merge($params, $params, $params, $params, $params),
		'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
		'processing_function_all' => 'ex_aequo2',
		'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word'),
		'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Top spammers, ordered by messages per day',
				'transformation_function' => 'top_spammers',
				'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
				'processing_function_all' => 'ex_aequo3',
				'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word'),
				'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left', 'left'),
			),
		),
	);
$queries[] = array(
		'title' => 'Messages per hour',
		'query' => "select h.hour hour, coalesce(a.shouts, 0) shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where (date_format(date, '%H')+1)%24=a.hour and deleted=0 and $filter group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where (date_format(date, '%H')+1)%24=a.hour and deleted=0 and $filter group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
				from (select lpad((date_format(date, '%H')+1) % 24, 2, '0') as hour, count(*) as shouts from shouts s where deleted = 0 and $filter group by hour) a right join hours_of_day h on (a.hour = h.hour)
				order by hour asc",
		'params' => array_merge($params, $params, $params),
		'processing_function' => 'messages_per_hour',
		'columns' => array('Hour', 'Messages', 'Top spammer', 'Most popular smiley'),
		'column_styles' => array('left', 'right', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Busiest hours',
				'transformation_function' => 'busiest_hours',
				'processing_function' => 'messages_per_hour',
				'columns' => array('Hour', 'Messages', 'Top spammer', 'Most popular smiley'),
				'column_styles' => array('left', 'right', 'left', 'left'),
			),
		),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select a.xday, a.shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where date_format(date, '%Y-%m-%d')=a.xday and deleted=0 and $filter group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where date_format(date, '%Y-%m-%d')=a.xday and deleted=0 and $filter group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
			from
				(select date_format(date_add(date, interval 1 hour), '%Y-%m-%d') xday, count(*) as shouts
					from shouts s where deleted = 0 and $filter
					group by xday
					order by count(*) desc
					limit 0, 10) a",
		'params' => array_merge($params, $params, $params),
		'processing_function' => function(&$row) {
				$link_parts = build_link_from_request('user', 'hour', 'smiley', 'period');

				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year$link_parts\">" . $row[0]['day'] . '</a>';
				spammer_smiley($row);
			},
		'columns' => array('Day', 'Messages', 'Top spammer', 'Most popular smiley'),
		'column_styles' => array('left', 'right', 'left', 'left'),
	);
if(!isset($_REQUEST['day'])) {
	$queries[] = array(
			'title' => 'Messages per month',
			'query' => "select date_format(date_add(date, interval 1 hour), '%Y-%m') monthx, count(*) as shouts,
						(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where date_format(date, '%Y-%m')=monthx and deleted=0 and $filter group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
						(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where date_format(date, '%Y-%m')=monthx and deleted=0 and $filter group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
					from shouts s
					where deleted = 0 and $filter 
					group by monthx
					order by monthx asc",
			'params' => array_merge($params, $params, $params),
			'processing_function' => 'messages_per_month',
			'columns' => array('Month', 'Messages', 'Top spammer', 'Most popular smiley'),
			'column_styles' => array('left', 'right', 'left', 'left'),
			'derived_queries' => array(
				array(
					'title' => 'Messages per month, ordered by number of messages',
					'transformation_function' => 'busiest_time',
					'processing_function' => 'messages_per_month',
					'processing_function_all' => 'ex_aequo2',
					'columns' => array('Position', 'Month', 'Messages', 'Top spammer', 'Most popular smiley'),
					'column_styles' => array('right', 'left', 'right', 'left', 'left'),
				),
			),
		);
}
if(!isset($_REQUEST['month'])) {
	$queries[] = array(
			'title' => 'Messages per year',
			'query' => "select date_format(date_add(date, interval 1 hour), '%Y') yearx, count(*) as shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where date_format(date, '%Y')=yearx and deleted=0 and $filter group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where date_format(date, '%Y')=yearx and deleted=0 and $filter group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
				from shouts s
				where deleted = 0 and $filter
				group by yearx
				order by yearx asc",
			'params' => array_merge($params, $params, $params),
			'processing_function' => 'messages_per_year',
			'columns' => array('Year', 'Messages', 'Top spammer', 'Most popular smiley'),
			'column_styles' => array('left', 'right', 'left', 'left'),
			'derived_queries' => array(
				array(
					'title' => 'Messages per year, ordered by number of messages',
					'transformation_function' => 'busiest_time',
					'processing_function' => 'messages_per_year',
					'processing_function_all' => 'ex_aequo2',
					'columns' => array('Position', 'Year', 'Messages', 'Top spammer', 'Most popular smiley'),
					'column_styles' => array('right', 'left', 'right', 'left', 'left'),
				),
			),
		);
}
$filter2 = str_replace(array('s.epoch', 's.id'), array('s2.epoch', 's2.id'), $filter);
$filter3 = str_replace(array('s.epoch', 's.id'), array('sh.epoch', 'sh.id'), $filter);
$queries[] = array(
		'title' => 'Smiley usage',
		'query' => "select s.filename filename, sum(count),
			(select concat(u.id, '$$', u.name, '$$', sum(ss2.count))
				from users u join shouts s2 on (u.id = s2.user) join shout_smilies ss2 on (s2.id = ss2.shout_id and s2.epoch = ss2.shout_epoch)
				where ss2.smiley = s.id and s2.deleted = 0 and $filter2
				group by s2.user
				order by sum(ss2.count) desc
				limit 0, 1) top
			from shout_smilies ss join smilies s on (ss.smiley = s.id) join shouts sh on (ss.shout_epoch = sh.epoch and ss.shout_id = sh.id) where sh.deleted = 0 and $filter3 group by ss.smiley, s.filename order by sum(count) desc",
		'processing_function' => function(&$row) {
				global $smilies;

				if(!isset($smilies)) {
					$query = 'SELECT id, filename FROM smilies';
					$smilies = db_query($query, array());
				}

				foreach($smilies as $smiley) {
					if($smiley['filename'] == $row[0]['filename']) {
						$smiley_id = $smiley['id'];
						break;
					}
				}

				$link_parts = build_link_from_request('day', 'month', 'year', 'user', 'hour', 'period');
				$row[0]['filename'] = '<a href="details.php?smiley=' . $smiley_id . $link_parts . '"><img src="images/smilies/' . $row[0]['filename'] . '" alt="" /></a>';

				$top = explode('$$', $row[0]['top']);
				$user_id = $top[0];
				$username = $top[1];
				$frequency = $top[2];
				$link = 'details.php?user=' . urlencode($username);
				$row[0]['top'] = "<a href=\"$link\">$username</a> (${frequency}x)";
			},
		'params' => array_merge($params, $params),
		'columns' => array('Smiley', 'Occurrences', 'Top user'),
		'column_styles' => array('right', 'right', 'left'),
	);
$queries[] = array(
		'title' => 'Word usage',
		'query' => "select w.word word, sum(count),
			(select concat(u.id, '$$', u.name, '$$', sum(sw2.count))
				from users u join shouts s2 on (u.id = s2.user) join shout_words sw2 on (s2.id = sw2.shout_id and s2.epoch = sw2.shout_epoch)
				where sw2.word = w.id and s2.deleted = 0 and $filter2
				group by s2.user
				order by sum(sw2.count) desc
				limit 0, 1) top
			from shout_words sw join words w on (sw.word = w.id) join shouts sh on (sw.shout_epoch = sh.epoch and sw.shout_id = sh.id) where sh.deleted = 0 and $filter3 group by sw.word, w.word order by sum(count) desc limit 0, 20",
		'processing_function' => function(&$row) {
				$link_parts = build_link_from_request('day', 'month', 'year', 'user', 'hour', 'period');
				$row[0]['word'] = '<a href="details.php?word=' . urlencode($row[0]['word']) . $link_parts . '">' . $row[0]['word'] . '</a>';

				$top = explode('$$', $row[0]['top']);
				$user_id = $top[0];
				$username = $top[1];
				$frequency = $top[2];
				$link = 'details.php?user=' . urlencode($username);
				$row[0]['top'] = "<a href=\"$link\">$username</a> (${frequency}x)";
			},
		'params' => array_merge($params, $params),
		'columns' => array('Word', 'Occurrences', 'Top user'),
		'column_styles' => array('right', 'right', 'left'),
	);
/*
$queries[] = array(
		'title' => '',
		'query' => "",
		'columns' => array(),
		'column_styles' => array(),
	);
 */
$query_total = array(
		'query' => "SELECT COUNT(*) shouts FROM shouts s WHERE deleted = 0 AND $filter",
		'params' => $params,
	);

$page_title = "Spam overview: $what";
$backlink = array(
		'url' => 'overview.php',
		'text' => 'Spam overview',
	);

require_once(dirname(__FILE__) . '/../lib/stats.php');

log_data();

