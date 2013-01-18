<?php
function add_user_link(&$row) {
	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . '">' . $row[0]['name'] . '</a>';
}

function messages_per_hour(&$row) {
	$row[0]['hour'] = '<a href="details.php?hour=' . $row[0]['hour'] . '">' . $row[0]['hour'] . '</a>';
}

function messages_per_month(&$row) {
	$parts = explode('-', $row[0]['month']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['month'] = "<a href=\"details.php?month=$month&amp;year=$year\">" . $row[0]['month'] . '</a>';
}

function messages_per_year(&$row) {
	$row[0]['year'] = "<a href=\"details.php?year=" . $row[0]['year'] . "\">" . $row[0]['year'] . '</a>';
}

$queries = array();
$queries[] = array(
		'title' => 'Top spammers',
		'query' => "select concat(@row:=@row+1, '.'), b.name, b.shouts, coalesce(b.shouts/ceil((b.last_shout-b.first_shout)/86400), 1) as average_shouts_per_day
			from (select a.name, a.shouts,
				(select unix_timestamp(min(date)) from shouts where user=a.id) as first_shout,
				(select unix_timestamp(max(date)) from shouts where user=a.id) as last_shout
				from (select u.id, u.name, count(*) as shouts from shouts s join users u
				on (s.user = u.id) where deleted = 0 group by u.id, u.name) a) b, (select @row:=0) c
			order by b.shouts desc",
		'processing_function' => 'add_user_link',
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
				on (s.user = u.id) where deleted = 0 group by u.id, u.name) a) b, (select @row:=0) c
			order by average_shouts_per_day desc",
		'processing_function' => 'add_user_link',
		'columns' => array('Position', 'Username', 'Messages', 'Average messages per day'),
		'column_styles' => array('right', 'left', 'right', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per hour',
		'query' => "select date_format(date, '%H') hour, count(*) as shouts from shouts where deleted = 0 group by hour order by hour asc",
		'processing_function' => 'messages_per_hour',
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest hours',
		'query' => "select date_format(date, '%H') hour, count(*) as shouts from shouts where deleted = 0 group by hour order by count(*) desc",
		'processing_function' => 'messages_per_hour',
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select date_format(date, '%Y-%m-%d') day, count(*) as shouts from shouts where deleted = 0 group by day order by count(*) desc limit 0, 10",
		'processing_function' => function(&$row) {
				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year\">" . $row[0]['day'] . '</a>';
			},
		'columns' => array('Day', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per month',
		'query' => "select date_format(date, '%Y-%m') month, count(*) as shouts from shouts where deleted = 0 group by month order by month asc",
		'processing_function' => 'messages_per_month',
		'columns' => array('Month', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per year',
		'query' => "select date_format(date, '%Y') year, count(*) as shouts from shouts where deleted = 0 group by year order by year asc",
		'processing_function' => 'messages_per_year',
		'columns' => array('Year', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per month, ordered by number of messages',
		'query' => "select concat(@row:=@row+1, '.'), month, shouts from (select date_format(date, '%Y-%m') month, count(*) as shouts from shouts c where deleted = 0 group by month order by shouts desc) a, (select @row:=0) c",
		'processing_function' => 'messages_per_month',
		'columns' => array('Position', 'Month', 'Messages'),
		'column_styles' => array('right', 'left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per year, ordered by number of messages',
		'query' => "select concat(@row:=@row+1, '.'), year, shouts from (select date_format(date, '%Y') year, count(*) as shouts from shouts c where deleted = 0 group by year order by shouts desc) a, (select @row:=0) c",
		'processing_function' => 'messages_per_year',
		'columns' => array('Position', 'Year', 'Messages'),
		'column_styles' => array('right', 'left', 'right'),
	);
/*
$queries[] = array(
		'title' => '',
		'query' => "",
		'columns' => array(),
		'column_styles' => array(),
	);
 */

$page_title = 'Spam overview';
$backlink = array(
		'url' => 'index.php',
		'text' => 'Chatbox archive',
	);

require_once('stats.php');

