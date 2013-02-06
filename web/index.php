<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

$default_page = 1;
$default_limit = 100;

if(isset($_GET['id']) && isset($_GET['epoch'])) {
	$id = $_GET['id'];
	if(!preg_match('/^[0-9]+$/', $id)) {
		die();
	}
	$epoch = $_GET['epoch'];
	if(!preg_match('/^[0-9]+$/', $epoch)) {
		die();
	}
	if(!isset($_GET['limit'])) {
		$limit = $default_limit;
	}
	else {
		$limit = $_GET['limit'];
		if(!preg_match('/^[0-9]+$/', $limit)) {
			$limit = $default_limit;
		}
	}

	$query = 'SELECT id, epoch FROM shouts WHERE id = ? and epoch = ?';
	$data = db_query($query, array($id, $epoch));
	if(count($data) != 1) {
		die();
	}

	$query = 'SELECT COUNT(*) shouts FROM shouts WHERE ((id > ? AND epoch = ?) OR epoch > ?) AND deleted = 0';
	$data = db_query($query, array($id, $epoch, $epoch));

	$page = floor($data[0]['shouts']/$limit)+1;

	header("Location: ?limit=$limit&page=$page#message${id}_$epoch");
	die();
}

$page = isset($_GET['page']) ? $_GET['page'] : $default_page;
$limit = isset($_GET['limit']) ? $_GET['limit'] : $default_limit;
if(!preg_match('/^[0-9]+$/', $page)) {
	$page = $default_page;
}
if(!preg_match('/^[0-9]+$/', $limit)) {
	$limit = $default_limit;
}
$offset = ($page-1)*$limit;

$limit = intval($limit);
$offset = intval($offset);

$ajax = (isset($_GET['ajax']) && $_GET['ajax'] == 'on');
$refresh = (isset($_GET['refresh']) && $_GET['refresh'] == 'on');

$filters = array('deleted = 0');
$params = array();
if(isset($_GET['text']) && trim($_GET['text']) != '') {
	$filters[] = 's.message LIKE ?';
	$params[] = '%' . $_GET['text'] . '%';
}
if(isset($_GET['user']) && trim($_GET['user']) != '') {
	$filters[] = 'u.name = ?';
	$params[] = $_GET['user'];
}
if(isset($_GET['date']) && trim($_GET['date']) != '') {
	$filters[] = "DATE_FORMAT(DATE_ADD(s.date, INTERVAL 1 HOUR), '%Y-%m-%d') = ?";
	$params[] = $_GET['date'];
}

$filter = implode(' AND ', $filters);
$query = "SELECT s.id id, s.epoch epoch, s.date date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE $filter ORDER BY s.epoch DESC, s.id DESC LIMIT ?, ?";
$params[] = intval($offset);
$params[] = intval($limit);
$db_data = db_query($query, $params);

// TODO move this function to the top of the file?
function process_smiley($match) {
	return "images/smilies/" . basename($match[0]);
}

$data = array();
foreach($db_data as $row) {
	// TODO simplify this
	$id = $row['id'];
	$epoch = $row['epoch'];
	$date = $row['date'];
	$color = $row['color'];
	$user_id = $row['user_id'];
	$user_name = $row['user_name'];
	$message = $row['message'];

	$datetime = new DateTime($date, new DateTimeZone('Europe/London'));
	$datetime->setTimezone((new DateTime())->getTimezone());
	$formatted_date = $datetime->format('[d-m-Y H:i]');
	$color = ($color == '-') ? 'user' : $color;
	$link = '?user=' . urlencode($user_name) . "&amp;limit=$limit";
	if(isset($_GET['text']) && trim($_GET['text']) != '') {
		$link .= '&amp;text=' . urlencode($_GET['text']);
	}

	// TODO scan for < and > inside href attributes

	$message = preg_replace_callback('+/?(pics|images)/([no]b/)?smilies/[^"]*\.(gif|png|jpg)+i', 'process_smiley', $message);
	$message = str_replace('/http:', 'http:', $message);
	$message = str_replace(' target="_blank"', '', $message);
	$message = str_replace(' border="0"', '', $message);
	$message = str_replace('"style="', '" style="', $message);
	$message = str_replace('</A>', '</a>', $message);

	$message = preg_replace_callback('/&#([0-9]+);/', 'unicode_character', $message);
	$message = preg_replace('/color=(#......)/', 'color="\1"', $message);

	$message = preg_replace('/<a /', '<a target="_blank" ', $message);

	// TODO problems with <embed> tag?
	$message = str_replace('width=&quot;200&quot; height=&quot;300&quot;', 'width="200" height="300"', $message);
	$data[] = array('date' => $formatted_date, 'color' => $color, 'user_id' => $user_id, 'user_name' => $user_name, 'message' => $message, 'user_link' => $link, 'id' => $id, 'epoch' => $epoch);
}

$query = 'SELECT COUNT(*) shouts FROM shouts WHERE deleted = 0';
$db_data = db_query($query);
$grand_total = $db_data[0]['shouts'];

$query = "SELECT COUNT(*) shouts FROM shouts s JOIN users u ON (s.user = u.id) WHERE $filter";
array_pop($params);
array_pop($params);
$db_data = db_query($query, $params);
$total_shouts = $db_data[0]['shouts'];

$page_count = ceil($total_shouts/$limit);
$link_parts = "?limit=$limit";
if(isset($_GET['text']) && trim($_GET['text']) != '') {
	$link_parts .= '&amp;text=' . urlencode($_GET['text']);
}
if(isset($_GET['user']) && trim($_GET['user']) != '') {
	$link_parts .= '&amp;user=' . urlencode($_GET['user']);
}
if(isset($_GET['date']) && trim($_GET['date']) != '') {
	$link_parts .= '&amp;date=' . urlencode($_GET['date']);
}
$previous_page = $page-1;
if($previous_page <= 0) {
	$previous_page = 1;
}
if($previous_page > $page_count) {
	$previous_page = $page_count;
}
$next_page = $page+1;
if($next_page <= 0) {
	$next_page = 1;
}
if($next_page > $page_count) {
	$next_page = $page_count;
}
$previous_link = "$link_parts&amp;page=$previous_page";
$next_link = "$link_parts&amp;page=$next_page";
$first_link = "$link_parts&amp;page=1";
$last_link = "$link_parts&amp;page=$page_count";
$generic_link = str_replace('&amp;', '&', "$link_parts&amp;page=");

$query = 'SELECT name FROM users ORDER BY name ASC';
$users = json_encode(array_map(function($a) { return $a['name']; }, db_query($query)));

// header('Content-Type: application/xhtml+xml; charset=utf-8');
header('Content-Type: text/html; charset=utf-8');

require_once(dirname(__FILE__) . '/../templates/pages/archive.php');

log_data();

