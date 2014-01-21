<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/user.php');

if(!isset($_REQUEST['username']) || !isset($_REQUEST['access_token']) || !isset($_REQUEST['message'])) {
	die();
}

if(!post($_REQUEST['username'], $_REQUEST['access_token'], $_REQUEST['message'])) {
	header('HTTP/1.1 500 Internal server error');
	die();
}

log_data();

