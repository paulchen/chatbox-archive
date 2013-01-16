<?php
require_once('common.php');

if(!isset($_REQUEST['user'])) {
	header('Location: overview.php');
	die();
}
$user = $_REQUEST['user'];

// TODO detail pages also for dates
$stmt = $mysqli->prepare('SELECT id FROM users WHERE name = ?');
$stmt->bind_param('s', $user);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();
if(!$user_id) {
	// TODO duplicate code
	header('Location: overview.php');
	die();
}

// TODO duplicate code
$queries = array();
$queries[] = array(
		'title' => 'Busiest hours',
		'query' => "select date_format(date, '%H') hour, count(*) as shouts from shouts where deleted = 0 and user = ? group by hour order by count(*) desc",
		'params' => array($user_id),
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select date_format(date, '%Y-%m-%d') day, count(*) as shouts from shouts where deleted = 0 and user = ? group by day order by count(*) desc limit 0, 10",
		'params' => array($user_id),
		'columns' => array('Day', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per month',
		'query' => "select date_format(date, '%Y-%m') month, count(*) as shouts from shouts where deleted = 0 and user = ? group by month order by month asc",
		'params' => array($user_id),
		'columns' => array('Month', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per year',
		'query' => "select date_format(date, '%Y') year, count(*) as shouts from shouts where deleted = 0 and user = ? group by year order by year asc",
		'params' => array($user_id),
		'columns' => array('Year', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per month, ordered by number of messages',
		'query' => "select concat(@row:=@row+1, '.'), month, shouts from (select date_format(date, '%Y-%m') month, count(*) as shouts from shouts c where deleted = 0 and user = ? group by month order by shouts desc) a, (select @row:=0) c",
		'params' => array($user_id),
		'columns' => array('Position', 'Month', 'Messages'),
		'column_styles' => array('right', 'left', 'right'),
	);
$queries[] = array(
		'title' => 'Messages per year, ordered by number of messages',
		'query' => "select concat(@row:=@row+1, '.'), year, shouts from (select date_format(date, '%Y') year, count(*) as shouts from shouts c where deleted = 0 and user = ? group by year order by shouts desc) a, (select @row:=0) c",
		'params' => array($user_id),
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

$page_title = "Spam overview for $user";
$backlink = array(
		'url' => 'overview.php',
		'text' => 'Spam overview',
	);

require_once('stats.php');

