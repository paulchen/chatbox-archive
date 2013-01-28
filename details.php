<?php
require_once('lib/common.php');

function overview_redirect() {
	header('Location: overview.php');
	die();
}

function add_user_link(&$row) {
	$link_parts = '';
	if(isset($_REQUEST['day'])) {
		$link_parts .= '&amp;day=' . $_REQUEST['day'];
	}
	if(isset($_REQUEST['month'])) {
		$link_parts .= '&amp;month=' . $_REQUEST['month'];
	}
	if(isset($_REQUEST['year'])) {
		$link_parts .= '&amp;year=' . $_REQUEST['year'];
	}
	if(isset($_REQUEST['hour'])) {
		$link_parts .= '&amp;hour=' . $_REQUEST['hour'];
	}

	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . $link_parts . '">' . $row[0]['name'] . '</a>';
}

function messages_per_hour(&$row) {
	$link_parts = '';
	if(isset($_REQUEST['day'])) {
		$link_parts .= '&amp;day=' . $_REQUEST['day'];
	}
	if(isset($_REQUEST['month'])) {
		$link_parts .= '&amp;month=' . $_REQUEST['month'];
	}
	if(isset($_REQUEST['year'])) {
		$link_parts .= '&amp;year=' . $_REQUEST['year'];
	}
	if(isset($_REQUEST['user'])) {
		$link_parts .= '&amp;user=' . urlencode($_REQUEST['user']);
	}

	$row[0]['hour'] = '<a href="details.php?hour=' . $row[0]['hour'] . $link_parts . '">' . $row[0]['hour'] . '</a>';
}

function messages_per_month(&$row) {
	$link_parts = '';
	if(isset($_REQUEST['user'])) {
		$link_parts .= '&amp;user=' . urlencode($_REQUEST['user']);
	}
	if(isset($_REQUEST['hour'])) {
		$link_parts .= '&amp;hour=' . $_REQUEST['hour'];
	}

	$parts = explode('-', $row[0]['month']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['month'] = "<a href=\"details.php?month=$month&amp;year=$year$link_parts\">" . $row[0]['month'] . '</a>';
}

function messages_per_year(&$row) {
	$link_parts = '';
	if(isset($_REQUEST['user'])) {
		$link_parts .= '&amp;user=' . urlencode($_REQUEST['user']);
	}
	if(isset($_REQUEST['hour'])) {
		$link_parts .= '&amp;hour=' . $_REQUEST['hour'];
	}

	$row[0]['year'] = "<a href=\"details.php?year=" . $row[0]['year'] . "$link_parts\">" . $row[0]['year'] . '</a>';
}

if(!isset($_REQUEST['user']) && !isset($_REQUEST['year']) && !isset($_REQUEST['hour'])) {
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
	$filter_parts[] = "date_format(date, '$date_format') = ?";
	$params[] = $date;
	$what_parts[] = $date;
}
if(isset($_REQUEST['user'])) {
	$filter_parts[] = "user = ?";
	$params[] = $user_id;
	$what_parts[] = $user;
}
$filter = implode(' AND ', $filter_parts);
$what = implode(', ', $what_parts);

$queries = array();
$queries[] = array(
		'title' => 'Top spammers',
		'query' => "select concat(@row:=@row+1, '.'), b.name, b.shouts, coalesce(b.shouts/ceil((b.last_shout-b.first_shout)/86400), 1) as average_shouts_per_day
			from (select a.name, a.shouts,
				(select unix_timestamp(min(date)) from shouts where user=a.id) as first_shout,
				(select unix_timestamp(max(date)) from shouts where user=a.id) as last_shout
				from (select u.id, u.name, count(*) as shouts from shouts s join users u
				on (s.user = u.id) where deleted = 0 and $filter group by u.id, u.name) a) b, (select @row:=0) c
			order by b.shouts desc, average_shouts_per_day desc, b.name asc",
		'params' => $params,
		'processing_function' => 'add_user_link',
		'processing_function_all' => 'ex_aequo2',
		'columns' => array('Position', 'Username', 'Messages', 'Average messages per day'),
		'column_styles' => array('right', 'left', 'right', 'right'),
	);
$queries[] = array(
		'title' => 'Top spammers, ordered by messages per day',
		'query' => "select concat(@row:=@row+1, '.'), b.name, b.shouts, coalesce(b.shouts/ceil((b.last_shout-b.first_shout)/86400), 1) as average_shouts_per_day
			from (select a.name, a.shouts,
				(select unix_timestamp(min(date)) from shouts where user=a.id) as first_shout,
				(select unix_timestamp(max(date)) from shouts where user=a.id) as last_shout
				from (select u.id, u.name, count(*) as shouts from shouts s join users u
				on (s.user = u.id) where deleted = 0 and $filter group by u.id, u.name) a) b, (select @row:=0) c
			order by average_shouts_per_day desc, b.shouts desc, b.name asc",
		'params' => $params,
		'processing_function' => 'add_user_link',
		'processing_function_all' => 'ex_aequo3',
		'columns' => array('Position', 'Username', 'Messages', 'Average messages per day'),
		'column_styles' => array('right', 'left', 'right', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per hour',
		'query' => "select h.hour hour, coalesce(a.shouts, 0) shouts from (select lpad((date_format(date, '%H')+1) % 24, 2, '0') as hour, count(*) as shouts from shouts where deleted = 0 and $filter group by hour) a right join hours_of_day h on (a.hour = h.hour) order by hour asc",
		'params' => $params,
		'processing_function' => 'messages_per_hour',
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest hours',
		'query' => "select lpad((date_format(date, '%H')+1) % 24, 2, '0') as hour, count(*) as shouts from shouts where deleted = 0 and $filter group by hour order by count(*) desc",
		'params' => $params,
		'processing_function' => 'messages_per_hour',
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select date_format(date, '%Y-%m-%d') day, count(*) as shouts from shouts where deleted = 0 and $filter group by day order by count(*) desc limit 0, 10",
		'params' => $params,
		'processing_function' => function(&$row) {
				$link_parts = '';
				if(isset($_REQUEST['hour'])) {
					$link_parts .= '&amp;hour=' . $_REQUEST['hour'];
				}
				if(isset($_REQUEST['user'])) {
					$link_parts .= '&amp;user=' . urlencode($_REQUEST['user']);
				}

				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year$link_parts\">" . $row[0]['day'] . '</a>';
			},
		'columns' => array('Day', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
if(!isset($_REQUEST['day'])) {
	$queries[] = array(
			'title' => 'Messages per month',
			'query' => "select date_format(date, '%Y-%m') month, count(*) as shouts from shouts where deleted = 0 and $filter group by month order by month asc",
			'params' => $params,
			'processing_function' => 'messages_per_month',
			'columns' => array('Month', 'Messages'),
			'column_styles' => array('left', 'right'),
		);
}
if(!isset($_REQUEST['month'])) {
	$queries[] = array(
			'title' => 'Messages per year',
			'query' => "select date_format(date, '%Y') year, count(*) as shouts from shouts where deleted = 0 and $filter group by year order by year asc",
			'params' => $params,
			'processing_function' => 'messages_per_year',
			'columns' => array('Year', 'Messages'),
			'column_styles' => array('left', 'right'),
		);
	$queries[] = array(
			'title' => 'Messages per month, ordered by number of messages',
			'query' => "select concat(@row:=@row+1, '.'), month, shouts from (select date_format(date, '%Y-%m') month, count(*) as shouts from shouts c where deleted = 0 and $filter group by month order by shouts desc, month asc) a, (select @row:=0) c",
			'params' => $params,
			'processing_function' => 'messages_per_month',
			'processing_function_all' => 'ex_aequo2',
			'columns' => array('Position', 'Month', 'Messages'),
			'column_styles' => array('right', 'left', 'right'),
		);
}
if(!isset($_REQUEST['year'])) {
	$queries[] = array(
			'title' => 'Messages per year, ordered by number of messages',
			'query' => "select concat(@row:=@row+1, '.'), year, shouts from (select date_format(date, '%Y') year, count(*) as shouts from shouts c where deleted = 0 and $filter group by year order by shouts desc, year asc) a, (select @row:=0) c",
			'params' => $params,
			'processing_function' => 'messages_per_year',
			'processing_function_all' => 'ex_aequo2',
			'columns' => array('Position', 'Year', 'Messages'),
			'column_styles' => array('right', 'left', 'right'),
		);
}
$queries[] = array(
		'title' => 'Smiley usage',
		'query' => "select s.filename filename, sum(count) from shout_smilies ss join smilies s on (ss.smiley = s.id) join shouts sh on (ss.shout_epoch = sh.epoch and ss.shout_id = sh.id) where sh.deleted = 0 and $filter group by ss.smiley, s.filename order by sum(count) desc",
		'processing_function' => function(&$row) {
				$row[0]['filename'] = '<img src="smilies/' . $row[0]['filename'] . '" alt="" />';
			},
		'params' => $params,
		'columns' => array('Smiley', 'Occurrences'),
		'column_styles' => array('right', 'right'),
	);
/*
$queries[] = array(
		'title' => '',
		'query' => "",
		'columns' => array(),
		'column_styles' => array(),
	);
 */

$page_title = "Spam overview for $what";
$backlink = array(
		'url' => 'overview.php',
		'text' => 'Spam overview',
	);

require_once(dirname(__FILE__) . '/lib/stats.php');

