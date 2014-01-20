<?php
function get_user_id($username) {
	$rows = db_query('SELECT id FROM users WHERE name = ?', array($username));
	if(count($rows) == 1) {
		return $rows[0]['id'];
	}

	$post = array(
		'ausername' => iconv('UTF-8', 'ISO-8859-1', $username),
		's' => '',
		'securitytoken' => 'guest',
		'do' => 'getall',
	);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	curl_setopt($curl, CURLOPT_URL, 'http://www.informatik-forum.at/memberlist.php');

	$data = curl_exec($curl);

	curl_close($curl);

	if(!preg_match_all('/"member\.php\?([0-9]+)[^"]*" class="username">(<B><Font[^>]*>)?([^<]*)</', $data, $matches, PREG_SET_ORDER)) {
		die('login_failed');
	}
	foreach($matches as $match) {
		if(iconv('ISO-8859-1', 'UTF-8', $match[3]) == $_REQUEST['username']) {
			$user_data = $match;
			break;
		}
	}
	if(!isset($user_data)) {
		die('login failed');
	}
	$user_id = $user_data[1];

	$category_name = '-';
	if(trim($user_data[2]) != '') {
		if(!preg_match('/Color="([^"]*)"/', $user_data[2], $match)) {
			die('login failed');
		}
		$category_name = $match[1];
	}

	$rows = db_query("SELECT id FROM user_categories WHERE name = ?", array($category_name));
	if(count($rows) == 0) {
		die('login failed');
	}
	$category = $rows[0]['id'];

	db_query('INSERT INTO users (id, name, category) VALUES (?, ?, ?)', array($user_id, $_REQUEST['username'], $category));

	return $user_id;
}

function login($username, $password) {
	global $tmpdir;

	$user_id = get_user_id($username);

	$post = array(
		'vb_login_username' => $username,
		'vb_login_password' => $password,
		'vb_login_password_hint' => 'Password',
		'cookieuser' => '1',
		'securitytoken' => 'guest',
		'do' => 'login',
		'vb_login_md5password' => '',
		'vb_login_md5password_utf' => '',
	);
	$tmpfile = tempnam($tmpdir, 'login_cookies_');

	$curl = curl_init();

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_URL, 'http://www.informatik-forum.at/login.php?do=login');
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	curl_setopt($curl, CURLOPT_COOKIEJAR, $tmpfile);

	curl_exec($curl);

	curl_setopt($curl, CURLOPT_POSTFIELDS, array());
	curl_setopt($curl, CURLOPT_POST, false);
	curl_setopt($curl, CURLOPT_URL, 'http://www.informatik-forum.at/faq.php');
	curl_setopt($curl, CURLOPT_COOKIEFILE, $tmpfile);
	curl_setopt($curl, CURLOPT_COOKIEJAR, $tmpfile);

	$data = curl_exec($curl);

	curl_close($curl);

	if(!preg_match('/var SECURITYTOKEN = "([0-9]+\-[0-9a-f]+)";/', $data, $match)) {
		return false;
	}

	$securitytoken = $match[1];
	$cookies = serialize(file_get_contents($tmpfile));
	unlink($tmpfile);

	db_query('DELETE FROM user_credentials WHERE id = ?', array($user_id));
	db_query('INSERT INTO user_credentials (id, password, cookie, securitytoken) VALUES (?, ?, ?, ?)', array($user_id, $_REQUEST['password'], $cookies, $securitytoken));

	return true;
}
