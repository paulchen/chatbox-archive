<?php

chdir(dirname(__FILE__));

require_once('../lib/common.php');

$last_update = get_setting('last_update');
$time_since_update = time() - $last_update;
$update_time = date('Y-m-d H:i:s', $last_update);

if($time_since_update < 60*60*24*1+60*60) {
	$ret_code = 0;
	$ret_message = "OK - last update: $update_time";
}
else if($time_since_update < 60*60*24*1+60*60*2) {
	$ret_code = 1;
	$ret_message = "WARNING - last update: $update_time";
}
else {
	$ret_code = 2;
	$ret_message = "CRITICAL - last update: $update_time";
}

echo "$ret_message\n";
die($ret_code);

