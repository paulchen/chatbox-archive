<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

chdir(dirname(__FILE__) . '/../request_log');

$files = glob('req*');
usort($files, function($a, $b) {
	return filemtime($a) > filemtime($b);
});

$files_count = count($files);
if($files_count > 0) {
	echo "Processing $files_count files\n";
	foreach($files as $file) {
		process_file($file);
	}
	echo "$files_count files processed\n";
}

function process_file($filename) {
	echo "Processing file $filename... ";
	$data = unserialize(file_get_contents($filename));

	$query = 'INSERT INTO requests (timestamp, url, ip, request_time, browser, username) VALUES (FROM_UNIXTIME(?), ?, ?, ?, ?, ?)';
	db_query($query, array($data['request_time'], $data['request_uri'], $data['remote_addr'], $data['end_time']-$data['start_time'], $data['user_agent'], $data['auth_user']));
	$request_id = db_last_insert_id();

	$query = 'INSERT INTO queries (request, timestamp, query, parameters, execution_time) VALUES (?, FROM_UNIXTIME(?), ?, ?, ?)';

	/* don't use a foreach loop as this would create an endless loop because of db_query() appending each query to $db_queries */
	$queries = count($data['db_queries']);
	for($a=0; $a<$queries; $a++) {
		$db_query = $data['db_queries'][$a];
		db_query($query, array($request_id, $db_query['timestamp'], $db_query['query'], $db_query['parameters'], $db_query['execution_time']));
	}

	unlink($filename);
	echo "done\n";
}

