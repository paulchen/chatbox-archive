<?php
if(!isset($_GET['url'])) {
	header('HTTP/1.1 404 Not Found');
	die('Not found');
}
$url = mb_substr($_SERVER['QUERY_STRING'], mb_strpos($_SERVER['QUERY_STRING'], 'url=', 0, 'UTF-8')+4, mb_strlen($_SERVER['QUERY_STRING'], 'UTF-8'), 'UTF-8');

$forward_headers = array('Expires', 'Cache-Control', 'Last-Modified', 'If-Modified-Since', 'If-None-Match', 'ETag');
$request_headers = array();
$apache_headers = apache_request_headers();
foreach($forward_headers as $header) {
	if(isset($apache_headers[$header])) {
		$value = $apache_headers[$header];
		$request_headers[] = "$header: $value";
	}
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
$data = curl_exec($curl);
$info = curl_getinfo($curl);
curl_close($curl);

$header_size = $info['header_size'];
$header = substr($data, 0, $header_size);
$body = substr($data, $header_size);

$response_headers = http_parse_headers($header);

function return_headers($headers) {
	global $forward_headers;

	foreach($headers as $key => $value) {
		if(in_array($key, $forward_headers)) {
			if(is_array($value)) {
				foreach($value as $item) {
					header("$key: $item");
				}
			}
			else {
				header("$key: $value");
			}
		}
	}
}

function internal_server_error() {
	header('HTTP/1.1 500 Internal Server Error');
	die('Internal server error');
}

function redirect($http_code, $url) {
	$code_names = array(301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect');
	if(!isset($code_names[$http_code])) {
		internal_server_error();
	}

	header("HTTP/1.1 $http_code {$code_names[$http_code]}");
	header("Location: proxy.php?url=$url");
	die();
}

switch($info['http_code']) {
	case 200:
		header('Content-Type: ' . $info['content_type']);
		header('Content-Length: ' . $info['download_content_length']);
		if(isset($response_headers['Content-Encoding'])) {
			header('Content-Encoding: ' . $response_headers['Content-Encoding']);
		}
		return_headers($response_headers);
		echo $body;
		die();

	case 301:
	case 302:
	case 303:
	case 307:
	case 308:
		redirect($info['http_code'], $info['redirect_url']);
		die();

	case 304:
		header('HTTP/1.1 304 Not Modified');
		die();

	case 404:
		header('HTTP/1.1 404 Not Found');
		die('Not found');

	default:
		internal_server_error();
}


