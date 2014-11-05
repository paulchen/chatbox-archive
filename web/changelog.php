<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

// TODO replace this by render_template?
ob_start();
require_once(dirname(__FILE__) . '/../templates/pages/changelog.php');
$data = ob_get_contents();
ob_clean();

xml_validate($data);
ob_start("ob_gzhandler");
echo $data;

log_data();

