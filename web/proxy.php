<?php
if(!isset($_GET['url'])) {
	header('HTTP/1.1 404 Not Found');
	die('Not found');
}
$url = mb_substr($_SERVER['QUERY_STRING'], mb_strpos($_SERVER['QUERY_STRING'], 'url=', 'UTF-8')+4, mb_strlen($_SERVER['QUERY_STRING'], 'UTF-8'), 'UTF-8');

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_URL, $url);
$data = curl_exec($curl);
$info = curl_getinfo($curl);
curl_close($curl);

if($info['http_code'] != 200) {
	if($info['http_code'] == 404) {
		header('HTTP/1.1 404 Not Found');
		die('Not found');
	}
	header('HTTP/1.1 500 Internal Server Error');
	die('Internal server error');
}

header('Content-Type: ' . $info['content_type']);
header('Content-Length: ' . $info['download_content_length']);
echo $data;

