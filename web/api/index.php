<?php

chdir(dirname(__FILE__) . '/../../');
require_once('./lib/common.php');

function get_parameter($name, $regex, $default) {
	if(!isset($_GET[$name])) {
		return $default;
	}
	$value = trim($_GET[$name]);
	if(!preg_match($regex, $value)) {
		return $default;
	}
	return $value;
}

$offset = get_parameter('offset', '/^[0-9]+$/', '0');
$limit = get_parameter('limit', '/^[0-9]+$/', '100');
$text = get_parameter('text', '/./', '');
$user = get_parameter('user', '/./', '');
$date = get_parameter('date', '/./', '');

$message_data = get_messages($text, $user, $date, $offset, $limit);

ob_start();
require_once('templates/api/success.php');
$data = ob_get_contents();
ob_end_clean();

$document = new DOMDocument;
$xml_error = false;
@$document->LoadXML($data) or $xml_error = true;
if($xml_error) {
	$filename = tempnam($tmpdir, 'api_');
	file_put_contents($filename, $data);

	$parameters = array('REQUEST_URI' => $_SERVER['REQUEST_URI']);
	$attachments = array($filename);
	send_mail('api_error.php', 'API validation error', $parameters, false, $attachments);

	unlink($filename);
}

$tidy = new tidy();
$tidy->parseString($data, array('indent' => true, 'input-xml' => true, 'wrap' => 1000), 'utf8');
$tidy->cleanRepair();

header('Content-Type: application/xml; charset=utf-8');
echo $tidy;

log_data();

