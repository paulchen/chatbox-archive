<?php
function add_user_link(&$row) {
	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . '">' . $row[0]['name'] . '</a>';
}

function spammer_smiley(&$row) {
	$parts = explode('$$', $row[0]['top_spammer']);
	$row[0]['top_spammer'] = "<a href=\"details.php?user={$parts[1]}\">{$parts[1]}</a> ({$parts[2]}x)";

	$parts = explode('$$', $row[0]['popular_smiley']);
	$row[0]['popular_smiley'] = "<a href=\"details.php?smiley={$parts[0]}\"><img src=\"images/smilies/{$parts[1]}\" alt=\"\" /></a> ({$parts[2]}x)";
}

function messages_per_hour(&$row) {
	$row[0]['hour'] = '<a href="details.php?hour=' . $row[0]['hour'] . '">' . $row[0]['hour'] . '</a>';
	spammer_smiley($row);
}

function messages_per_month(&$row) {
	$parts = explode('-', $row[0]['month']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['month'] = "<a href=\"details.php?month={$parts[1]}&amp;year={$parts[0]}\">{$row[0]['month']}</a>";
	spammer_smiley($row);
}

function messages_per_year(&$row) {
	$row[0]['year'] = "<a href=\"details.php?year=" . $row[0]['year'] . "\">" . $row[0]['year'] . '</a>';
	spammer_smiley($row);
}

$queries = array();
$queries[] = array(
		'title' => 'Top spammers',
		'query' => "select concat(@row:=@row+1, '.'), b.name, b.shouts, coalesce(b.shouts/ceil((b.last_shout-b.first_shout)/86400), 1) as average_shouts_per_day, b.smilies, b.smilies/b.shouts as average_smilies_per_message, b.smiley_info
			from (select a.name, a.shouts, a.smilies,
				(select unix_timestamp(min(date)) from shouts where user=a.id and deleted=0) as first_shout,
				(select unix_timestamp(max(date)) from shouts where user=a.id and deleted=0) as last_shout,
				(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count))
					from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id)
					where s.user = a.id and deleted = 0
					group by ss.smiley, sm.filename
					order by sum(ss.count) desc
					limit 0, 1) as smiley_info
				from (select u.id, u.name, count(distinct s.id) as shouts, coalesce(sum(ss.count), 0) as smilies from shouts s join users u
				on (s.user = u.id) left join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch)
				where deleted = 0 group by u.id, u.name) a) b, (select @row:=0) c
			order by b.shouts desc, average_shouts_per_day desc, b.name asc",
		'processing_function' => array('add_user_link', 'smiley_column'),
		'processing_function_all' => 'ex_aequo2',
		'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley'),
		'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Top spammers, ordered by messages per day',
				'transformation_function' => 'top_spammers',
				'processing_function' => array('add_user_link', 'smiley_column'),
				'processing_function_all' => 'ex_aequo3',
				'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley'),
				'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left'),
			),
		),
	);
$queries[] = array(
		'title' => 'Messages per hour',
		'query' => "select a.hour hour, coalesce(a.shouts, 0) shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where s.hour=a.hour and deleted=0 group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where s.hour=a.hour and deleted=0 group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
				from (select lpad(hour, 2, '0') as hour, count(*) as shouts from shouts where deleted = 0 group by hour) a right join hours_of_day h on (a.hour = h.hour)
				order by hour asc",
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
		'query' => "select a.day, a.shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where s.day=a.qday and s.month=a.qmonth and s.year=a.qyear and deleted = 0 group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where s.day=a.qday and s.month=a.qmonth and s.year=a.qyear and deleted=0 group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
			from
				(select date_format(date, '%Y-%m-%d') day, date_format(date, '%Y') qyear, date_format(date, '%m') qmonth, date_format(date, '%d') qday, count(*) as shouts
					from shouts where deleted = 0
					group by day, qyear, qmonth, qday
					order by count(*) desc
					limit 0, 10) a",
		'processing_function' => function(&$row) {
				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year\">" . $row[0]['day'] . '</a>';
				spammer_smiley($row);
			},
		'columns' => array('Day', 'Messages', 'Top spammer', 'Most popular smiley'),
		'column_styles' => array('left', 'right', 'left', 'left'),
	);
$queries[] = array(
		'title' => 'Messages per month',
		'query' => "select date_format(date, '%Y-%m') month, count(*) as shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where s.month=date_format(sx.date, '%m') and s.year=date_format(sx.date, '%Y') and deleted=0 group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where s.month=date_format(sx.date, '%m') and s.year=date_format(sx.date, '%Y') and deleted=0 group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
				from shouts sx
				where deleted = 0
				group by month, year
				order by year asc, month asc",
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
$queries[] = array(
		'title' => 'Messages per year',
		'query' => "select date_format(date, '%Y') year, count(*) as shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where date_format(date, '%Y')=year and deleted=0 group by s.user order by count(s.id) desc limit 0, 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where date_format(date, '%Y')=year and deleted=0 group by ss.smiley order by sum(ss.count) desc limit 0, 1) popular_smiley
	       		from shouts
			where deleted = 0
			group by year
			order by year asc",
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
$queries[] = array(
		'title' => 'Smiley usage',
		'query' => "select s.filename filename, sum(count),
			(select concat(u.id, '$$', u.name, '$$', sum(ss2.count))
				from users u join shouts s2 on (u.id = s2.user) join shout_smilies ss2 on (s2.id = ss2.shout_id and s2.epoch = ss2.shout_epoch)
				where ss2.smiley = s.id and s2.deleted = 0
				group by s2.user
				order by sum(ss2.count) desc
				limit 0, 1) top
			from shout_smilies ss join smilies s on (ss.smiley = s.id) join shouts sh on (ss.shout_epoch = sh.epoch and ss.shout_id = sh.id) where sh.deleted = 0 group by ss.smiley, s.filename order by sum(count) desc",
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

				$row[0]['filename'] = '<a href="details.php?smiley=' . $smiley_id . '"><img src="images/smilies/' . $row[0]['filename'] . '" alt="" /></a>';

				$top = explode('$$', $row[0]['top']);
				$user_id = $top[0];
				$username = $top[1];
				$frequency = $top[2];
				$row[0]['top'] = "$username (${frequency}x)";
			},
		'columns' => array('Smiley', 'Occurrences', 'Top user'),
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
		'query' => 'SELECT COUNT(*) shouts FROM shouts WHERE deleted = 0',
		'params' => array(),
	);

$page_title = 'Spam overview';
$backlink = array(
		'url' => 'index.php',
		'text' => 'Chatbox archive',
	);

require_once(dirname(__FILE__) . '/../lib/common.php');

/* cached data from misc/update_stats.php */
$extra_stats = $memcached->get("${memcached_prefix}_stats_min_max");

require_once(dirname(__FILE__) . '/../lib/stats.php');

log_data();

