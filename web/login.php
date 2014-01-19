<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/user.php');

if(isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
	if(!login($_REQUEST['username'], $_REQUEST['password'])) {
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

