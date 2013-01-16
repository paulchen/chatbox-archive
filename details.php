<?php
require_once('common.php');

function overview_redirect() {
	header('Location: overview.php');
	die();
}

if(!isset($_REQUEST['user']) && !isset($_REQUEST['year'])) {
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

	$stmt = $mysqli->prepare('SELECT id FROM users WHERE name = ?');
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$stmt->bind_result($user_id);
	$stmt->fetch();
	$stmt->close();
	if(!$user_id) {
		overview_redirect();
	}
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

if(isset($_REQUEST['user']) && isset($_REQUEST['year'])) {
	$filter = "user = ? AND date_format(date, '$date_format') = ?";
	$params = array($user_id, $date);
	$what = "$user, $date";
}
else if(isset($_REQUEST['user'])) {
	$filter = "user = ?";
	$params = array($user_id);
	$what = $user;
}
else {
	$filter = "date_format(date, '$date_format') = ?";
	$params = array($date);
	$what = $date;
}

$queries = array();
$queries[] = array(
		'title' => 'Busiest hours',
		'query' => "select date_format(date, '%H') hour, count(*) as shouts from shouts where deleted = 0 and $filter group by hour order by count(*) desc",
		'params' => $params,
		'columns' => array('Hour', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select date_format(date, '%Y-%m-%d') day, count(*) as shouts from shouts where deleted = 0 and $filter group by day order by count(*) desc limit 0, 10",
		'params' => $params,
		'columns' => array('Day', 'Messages'),
		'column_styles' => array('left', 'right'),
	);
if(!isset($_REQUEST['day'])) {
	$queries[] = array(
			'title' => 'Messages per month',
			'query' => "select date_format(date, '%Y-%m') month, count(*) as shouts from shouts where deleted = 0 and $filter group by month order by month asc",
			'params' => $params,
			'columns' => array('Month', 'Messages'),
			'column_styles' => array('left', 'right'),
		);
}
if(!isset($_REQUEST['month'])) {
	$queries[] = array(
			'title' => 'Messages per year',
			'query' => "select date_format(date, '%Y') year, count(*) as shouts from shouts where deleted = 0 and $filter group by year order by year asc",
			'params' => $params,
			'columns' => array('Year', 'Messages'),
			'column_styles' => array('left', 'right'),
		);
	$queries[] = array(
			'title' => 'Messages per month, ordered by number of messages',
			'query' => "select concat(@row:=@row+1, '.'), month, shouts from (select date_format(date, '%Y-%m') month, count(*) as shouts from shouts c where deleted = 0 and $filter group by month order by shouts desc) a, (select @row:=0) c",
			'params' => $params,
			'columns' => array('Position', 'Month', 'Messages'),
			'column_styles' => array('right', 'left', 'right'),
		);
}
if(!isset($_REQUEST['year'])) {
	$queries[] = array(
			'title' => 'Messages per year, ordered by number of messages',
			'query' => "select concat(@row:=@row+1, '.'), year, shouts from (select date_format(date, '%Y') year, count(*) as shouts from shouts c where deleted = 0 and $filter group by year order by shouts desc) a, (select @row:=0) c",
			'params' => $params,
			'columns' => array('Position', 'Year', 'Messages'),
			'column_styles' => array('right', 'left', 'right'),
		);
}
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

require_once('stats.php');

