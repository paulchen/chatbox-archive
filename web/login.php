<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/user.php');

if(isset($_REQUEST['username']) || isset($_REQUEST['password']) || isset($_REQUEST['token1']) || isset($_REQUEST['token2'])) {
	if(!isset($_REQUEST['username']) || !isset($_REQUEST['password']) || !isset($_REQUEST['token1']) || !isset($_REQUEST['token2'])) {
		die('not all fields specified');
	}
	if(trim($_REQUEST['token1']) == '') {
		die('empty access token');
	}
	if($_REQUEST['token1'] != $_REQUEST['token2']) {
		die('access tokens differ');
	}

	if(!login($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['token1'])) {
		die('login failed');
	}
	die('login successful');
}

ob_start();
require_once(dirname(__FILE__) . '/../templates/pages/login.php');
$data = ob_get_contents();
ob_clean();

xml_validate($data);
# ob_start("ob_gzhandler");
echo $data;

log_data();

