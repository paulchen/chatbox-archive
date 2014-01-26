<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/user.php');

$base_url = 'https://rueckgr.at/~paulchen/chatbox/details.php';

$messages = array();
$queries = array();

$day = date('d', time()-86400);
$month = date('m', time()-86400);
$year = date('Y', time()-86400);
$queries[] = array('name' => 'the last 24 hours', 'filter' => "day = ? AND month = ? AND year = ?", 'params' => array($day, $month, $year), 'details_link' => "$base_url?day=$day&month=$month&year=$year");

if(date('w') == 1) {
	$params = array();
	$list_items = array();

	for($a=1; $a<8; $a++) {
		$new_day = date('d', time()-86400*$a);
		$new_month = date('m', time()-86400*$a);
		$new_year = date('Y', time()-86400*$a);

		$params[] = $new_day;
		$params[] = $new_month;
		$params[] = $new_year;

		$list_items[] = '(?, ?, ?)';
	}

	$list = implode(', ', $list_items);
	$queries[] = array('name' => 'the last week', 'filter' => "(day, month, year) IN ($list)", 'params' => $params);
}
if(date('d') == '01') {
	$month = date('m')-1;
	$year = date('Y');
	if($month == 0) {
		$month = 12;
		$year--;
	}
	else if($month == 9) {
		$month = '09';
	}

	$monthnames = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	$queries[] = array('name' => "{$monthnames[$month]} $year", 'filter' => 'month = ? AND year = ?', 'params' => array($month, $year), 'details_link' => "$base_url?month=$month&year=$year");
}
if(date('dm') == '0101') {
	$messages[] = 'Happy new year! :cheer:';
	$year = date('Y')-1;
	$queries[] = array('name' => "$year", 'filter' => 'year = ?', 'params' => array($year), 'details_link' => "$base_url?year=$year");
}

$urls = array();

function format_username($user) {
	global $base_url, $urls;

	$username = $user['name'];
	$color = $user['color'];

	$url = "$base_url?user=" . urlencode($username);
	$urls[] = $url;

	$ret = $username;
	if($color != '-') {
		$ret = "[b][color=$color]{$ret}[/color][/b]";
	}

	return "[url=$url]{$ret}[/url]";
}

$max_rank = 5;
foreach($queries as $query) {
	$data = db_query("SELECT u.name AS name, uc.color, COUNT(DISTINCT s.id) count FROM shouts s JOIN users u ON (s.user=u.id) JOIN user_categories uc ON (u.category=uc.id) WHERE s.deleted=0 AND {$query['filter']} GROUP BY u.id, u.name, uc.color ORDER BY count DESC, u.name ASC", $query['params']);

	$total = 0;
	$top_spammers = '';
	for($rank=1; $rank<=count($data); $rank++) {
		$current_rank = $rank;
		$total += $data[$rank-1]['count'];
		if($rank <= $max_rank) {
			$usernames = array(format_username($data[$rank-1]));
			while($rank<count($data) && $data[$rank-1]['count'] == $data[$rank]['count']) {
				$usernames[] = format_username($data[$rank]);
				$total += $data[$rank]['count'];
				$rank++;
			}

			if($top_spammers != '') {
				$top_spammers .= ', ';
			}
			$top_spammers .= "$current_rank. " . implode('/', $usernames) . ' (';
			if(count($usernames) > 1) {
				$top_spammers .= 'each ';
			}
			$top_spammers .= $data[$rank-1]['count'] . ')';
		}
	}

	if(isset($query['details_link'])) {
		$messages[] = "Messages in {$query['name']}: $total; top spammers: $top_spammers; [url={$query['details_link']}]more details[/url]";
	}
	else {
		$messages[] = "Messages in {$query['name']}: $total; top spammers: $top_spammers";
	}
}

safe_login($forum_user, $forum_pass, $forum_pass);

foreach($messages as $message) {
	post($forum_user, $forum_pass, $message);
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_USERPWD, "update:aeBie6in");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, "signanzbot");
foreach($queries as $query) {
	if(isset($query['details_link'])) {
		$urls[] = $query['details_link'];
	}
}
foreach($urls as $url) {
	curl_setopt($curl, CURLOPT_URL, str_replace(' ', '%20', $url));
	curl_exec($curl);
}
curl_close($curl);

log_data();

