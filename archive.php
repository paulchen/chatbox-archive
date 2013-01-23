<?php
require_once('lib/common.php');

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
	$data = $db->query($query, array($id, $epoch));
	if(!$found) {
		die();
	}

	$query = 'SELECT COUNT(*) shouts FROM shouts WHERE (id > ? AND epoch = ?) OR epoch > ?';
	$data = $db->query($quety, array($id, $epoch, $epoch));

	$page = floor(($data[0]['shouts']+1)/$limit)+1;

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

if(isset($_GET['text']) && trim($_GET['text']) != '') {
	if (isset($_GET['user']) && trim($_GET['user']) != '') {
		$query = 'SELECT s.id id, s.epoch epoch, s.date date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE u.name = ? AND s.message LIKE ? AND deleted = 0 ORDER BY s.epoch DESC, s.id DESC LIMIT ?, ?';
		$text_filter = '%' . $_GET['text'] . '%';
		$user = $_GET['user'];
		$params = array($user, $text_filter, $offset, $limit);
	}
	else {
		$query = 'SELECT s.id id, s.epoch epoch, s.date date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE s.message LIKE ? AND deleted = 0 ORDER BY s.epoch DESC, s.id DESC LIMIT ?, ?';
		$text_filter = '%' . $_GET['text'] . '%';
		$params = array($text_filter, $offset, $limit);
	}
}
else if (isset($_GET['user']) && trim($_GET['user']) != '') {
	$query = 'SELECT s.id id, s.epoch epoch, s.date date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE u.name = ? AND deleted = 0 ORDER BY s.epoch DESC, s.id DESC LIMIT ?, ?';
	$user = $_GET['user'];
	$params = array($user, $offset, $limit);
}
else {
	$query = 'SELECT s.id id, s.epoch epoch, s.date date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE deleted = 0 ORDER BY s.epoch DESC, s.id DESC LIMIT ?, ?';
	$params = array($offset, $limit);
}

$db_data = db_query($query, $params);

// $stmt->bind_result(array($id, $epoch, $date, $color, $user_id, $user_name, $message));
$data = array();
// TODO simplify this
$patterns = array('pics/nb/smilies/', 'images/smilies/', 'images/nb/smilies/', 'images/ob/smilies', 'pics/ob/smilies');
$replacements = array('http://www.informatik-forum.at/pics/nb/smilies/', 'http://www.informatik-forum.at/images/smilies/', 'http://www.informatik-forum.at/images/nb/smilies/', 'http://www.informatik-forum.at/images/ob/smilies', 'http://www.informatik-forum.at/pics/ob/smilies');
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
	
	// TODO simplify this
	$message = str_replace($patterns, $replacements, $message);
	$message = str_replace('/http:', 'http:', $message);
	$message = str_replace(' target="_blank"', '', $message);
	$message = str_replace(' border="0"', '', $message);
	$message = str_replace('"style="', '" style="', $message);
	$message = str_replace('</A>', '</a>', $message);

	$message = preg_replace_callback('/&#([0-9]+);/', 'unicode_character', $message);
	$message = preg_replace('/color=(#......)/', 'color="\1"', $message);

	// TODO problems with <embed> tag?
	$message = str_replace('width=&quot;200&quot; height=&quot;300&quot;', 'width="200" height="300"', $message);
	$data[] = array('date' => $formatted_date, 'color' => $color, 'user_id' => $user_id, 'user_name' => $user_name, 'message' => $message, 'user_link' => $link, 'id' => $id, 'epoch' => $epoch);
}

if(isset($_GET['text']) && trim($_GET['text']) != '') {
	$text = '%' . $_GET['text'] . '%';
	if(isset($_GET['user']) && trim($_GET['user']) != '') {
		$query = 'SELECT COUNT(*) shouts FROM shouts s JOIN users u ON (s.user = u.id) WHERE message LIKE ? AND u.name = ?';
		$params = array($text, $_GET['user']);
	}
	else {
		$query = 'SELECT COUNT(*) shouts FROM shouts WHERE message LIKE ?';
		$params = array($text);
	}
}
else if(isset($_GET['user']) && trim($_GET['user']) != '') {
	$query = 'SELECT COUNT(*) shouts FROM shouts s JOIN users u ON (s.user = u.id) WHERE u.name = ?';
	$params = array($_GET['user']);
}
else {
	$query = 'SELECT COUNT(*) shouts FROM shouts';
	$params = array();
}
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

header('Content-Type: application/xhtml+xml; charset=utf-8');
echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Chatbox archive</title>
	<style type="text/css">
	body { font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif; font-size: 13px; }
	table { border: none; }
	td.date, td.user { white-space: nowrap; }
	a { text-decoration: none; color: #417394; }
	a.user { color: #417394; }
	a.purple { color: purple; font-weight: bold; }
	a.green { color: green; font-weight: bold; }
	a.red { color: red; font-weight: bold; }
	a.blue { color: blue; font-weight: bold; }
	td.date > a { color: black; }
	td.date > a:hover { color: red; }
	a:hover { color: red; }
	img { border: none; }
	</style>
</head>
<body>
	<h1>Chatbox archive</h1>
	<div>
		<a href="overview.php">Spam overview</a>
		<fieldset><legend>Filters</legend>
		<form method="get" action="<?php echo htmlentities($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
		<table>
		<tr><td>Text:</td><td><input type="text" name="text" value="<?php if(isset($_GET['text'])) echo htmlentities($_GET['text'], ENT_QUOTES, 'UTF-8') ?>" /></td></tr>
		<tr><td>User:</td><td><input type="text" name="user" value="<?php if(isset($_GET['user'])) echo htmlentities($_GET['user'], ENT_QUOTES, 'UTF-8') ?>" /></td></tr>
		<tr><td>Messages per page:</td><td><input type="text" name="limit" value="<?php echo $limit; ?>" /></td></tr>
		<tr><td>Page:</td><td><input type="text" name="page" value="<?php echo $page; ?>" /> (of <?php echo $page_count; ?>) <a href="<?php echo $first_link ?>">First</a> <a href="<?php echo $previous_link ?>">Previous</a> <a href="<?php echo $next_link ?>">Next</a> <a href="<?php echo $last_link ?>">Last</a></td></tr>
		<tr><td></td><td><input type="submit" value="Filter" /><input type="button" value="Reset" onclick="document.location.href='?';" /></td></tr>
		</table>
		</form>
		</fieldset>
		<table>
			<?php foreach($data as $row): ?>
				<tr>
					<td class="date"><a id="message<?php echo $row['id'] . '_' . $row['epoch'] ?>"></a><a href="?limit=<?php echo $limit ?>&amp;id=<?php echo $row['id'] . '&amp;epoch=' . $row['epoch'] ?>"><?php echo $row['date'] ?></a></td>
					<td class="user"><a class="<?php echo $row['color'] ?>" href="<?php echo $row['user_link'] ?>"><?php echo $row['user_name'] ?></a></td>
					<td class="message"><?php echo $row['message'] ?></td>
				</tr>
			<?php endforeach; ?>
		</table>		
		<div style="padding-top: 15px; padding-left: 5px;">
			Page <?php echo $page; ?> of <?php echo $page_count; ?> &ndash; <a href="<?php echo $first_link ?>">First</a> <a href="<?php echo $previous_link ?>">Previous</a> <a href="<?php echo $next_link ?>">Next</a> <a href="<?php echo $last_link ?>">Last</a>
		</div>
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
