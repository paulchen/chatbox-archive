<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

$base_url = 'https://rueckgr.at/~paulchen/chatbox/details.php';

$messages = array();
$queries = array();

$day = date('d', time()-86400);
$month = date('m', time()-86400);
$year = date('Y', time()-86400);
$queries[] = array('name' => 'the last 24 hours', 'filter' => "NOW()-s.date < interval '1 days'", 'params' => array(), 'details_link' => "$base_url?day=$day&month=$month&year=$year");

if(date('w') == 1) {
	$queries[] = array('name' => 'the last week', 'filter' => "NOW()-s.date < interval '7 days'", 'params' => array());
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

$max_rank = 5;
foreach($queries as $query) {
	$data = db_query("SELECT u.name AS name, COUNT(*) count FROM shouts s JOIN users u ON (s.user=u.id) WHERE s.deleted=0 AND {$query['filter']} GROUP BY u.id, u.name ORDER BY count DESC, u.name ASC", $query['params']);
	$total = 0;
	$top_spammers = '';
	for($rank=1; $rank<=count($data); $rank++) {
		$current_rank = $rank;
		$total += $data[$rank-1]['count'];
		if($rank <= $max_rank) {
			$usernames = array($data[$rank-1]['name']);
			while($rank<count($data) && $data[$rank-1]['count'] == $data[$rank]['count']) {
				$usernames[] = $data[$rank]['name'];
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

$script = dirname(__FILE__) . '/../post.sh';

foreach($messages as $message) {
	$message = rawurlencode(iconv('UTF-8', 'ISO-8859-1//IGNORE', $message));
	passthru("$script '$message'");
}

$curl = curl_init();
foreach($queries as $query) {
	if(isset($query['details_link'])) {
		curl_setopt($curl, CURLOPT_URL, $query['details_link']);
		curl_setopt($curl, CURLOPT_USERPWD, "update:aeBie6in");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, "signanzbot");
		curl_exec($curl);
	}
}
curl_close($curl);

