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
preg_match_all('/<a href="member.php\?u=([0-9]+)" title="[^"]+">(<B><Font Color="([a-z]+)">)?([^<]+)</', $data, $matches, PREG_PATTERN_ORDER);
preg_match_all('/Invisible/', $data, $invisibles, PREG_SET_ORDER);

if(count($matches[1]) > 0) {
	$placeholders = array();
	$parameters = array();
	foreach($matches[1] as $user) {
		$placeholders[] = '?';
		$parameters[] = trim($user);
	}

	$query = 'SELECT id FROM users WHERE id IN (' . implode(', ', $placeholders) . ')';
	$result = db_query($query, $parameters);
	if(count($result) != count($parameters)) {
		$data = db_query('SELECT id, color FROM user_categories');
		$categories = array();
		foreach($data as $row) {
			$categories[$row['color']] = $row['id'];
		}

		$found_users = array_map(function($a) { return $a['id']; }, $result);
		foreach($parameters as $user) {
			if(!in_array($user, $found_users)) {
				foreach($matches[1] as $index => $id) {
					if(trim($id) == $user) {
						$category = trim($matches[3][$index]);
						if($category == '') {
							$category = '-';
						}
						$category_id = $categories[$category];
						$name = trim($matches[4][$index]);
						db_query('INSERT INTO users (id, name, category) VALUES (?, ?, ?)', array($user, $name, $category_id));

						break;
					}
				}
			}
		}
	}

	$query = 'INSERT INTO online_users (user_id) VALUES (' . implode('), (', $placeholders) . ')';
	db_query($query, $parameters);
}

db_query('INSERT INTO invisible_users (users) VALUES (?)', array(count($invisibles)));

die(count($invisibles)+count($matches[1]));

