<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/user.php');

if($argc != 2) {
	die();
}

safe_login($forum_user, $forum_pass, $forum_pass);

$message = rawurlencode(iconv('UTF-8', 'ISO-8859-1//IGNORE', $argv[1]));

post($forum_user, $forum_pass, $message);

log_data();

