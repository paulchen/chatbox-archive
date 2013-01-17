<?php
function add_user_link(&$row) {
	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . '">' . $row[0]['name'] . '</a>';
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
		'title' => 'Busiest hours',
		'query' => "select date_format(date, '%H') hour, count(*) as shouts from shouts where deleted = 0 group by hour order by count(*) desc",
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select date_format(date, '%Y-%m-%d') day, count(*) as shouts from shouts where deleted = 0 group by day order by count(*) desc limit 0, 10",
		'columns' => array('Day', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per month',
		'query' => "select date_format(date, '%Y-%m') month, count(*) as shouts from shouts where deleted = 0 group by month order by month asc",
		'columns' => array('Month', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per year',
		'query' => "select date_format(date, '%Y') year, count(*) as shouts from shouts where deleted = 0 group by year order by year asc",
		'columns' => array('Year', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per month, ordered by number of messages',
		'query' => "select concat(@row:=@row+1, '.'), month, shouts from (select date_format(date, '%Y-%m') month, count(*) as shouts from shouts c where deleted = 0 group by month order by shouts desc) a, (select @row:=0) c",
		'columns' => array('Position', 'Month', 'Messages'),
		'column_styles' => array('right', 'left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per year, ordered by number of messages',
		'query' => "select concat(@row:=@row+1, '.'), year, shouts from (select date_format(date, '%Y') year, count(*) as shouts from shouts c where deleted = 0 group by year order by shouts desc) a, (select @row:=0) c",
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

