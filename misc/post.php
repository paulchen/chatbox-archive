<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/user.php');

if($argc != 2) {
	die();
}

safe_login($forum_user, $forum_pass, $forum_pass);

post($forum_user, $forum_pass, $argv[1]);

log_data();

