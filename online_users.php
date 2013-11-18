<?php
// when invoked via browser, do nothing
if(!defined('STDIN') && !defined($argc)) {
	die();
}

if($argc != 2) {
	die();
}

require_once('lib/common.php');

$data = file_get_contents($argv[1]);
preg_match_all('/<a href="member.php\?u=([0-9]+)" title="[^"]+">/', $data, $matches, PREG_PATTERN_ORDER);
if(count($matches[1]) > 0) {
	$query = 'INSERT INTO online_users ("user") VALUES ';
	$parameters = array();
	foreach($matches[1] as $user) {
		$query .= '(?),';
		$parameters[] = $user;
	}
	$query = substr($query, 0, strlen($query)-1);
	db_query($query, $parameters);
}

preg_match_all('/Invisible/', $data, $invisibles, PREG_SET_ORDER);
db_query('INSERT INTO invisible_users (users) VALUES (?)', array(count($invisibles)));

die(count($invisibles)+count($matches[1]));

