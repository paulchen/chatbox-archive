<?php
require_once('config.php');

// TODO common.php?
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
$mysqli->query('SET NAMES utf8');

require_once('auth.php');

$default_page = 1;
$default_limit = 100;

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
		$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(s.date) date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE u.name = ? AND s.message LIKE ? ORDER BY s.id DESC LIMIT ?, ?');
		$text_filter = '%' . $_GET['text'] . '%';
		$user = $_GET['user'];
		$stmt->bind_param('ssii', $user, $text_filter, $offset, $limit);
	}
	else {
		$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(s.date) date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE s.message LIKE ? ORDER BY s.id DESC LIMIT ?, ?');
		$text_filter = '%' . $_GET['text'] . '%';
		$stmt->bind_param('sii', $text_filter, $offset, $limit);
	}
}
else if (isset($_GET['user']) && trim($_GET['user']) != '') {
	$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(s.date) date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) WHERE u.name = ? ORDER BY s.id DESC LIMIT ?, ?');
	$user = $_GET['user'];
	$stmt->bind_param('sii', $user, $offset, $limit);
}
else {
	$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(s.date) date, c.color color, u.id user_id, u.name user_name, message FROM shouts s JOIN users u ON (s.user = u.id) JOIN user_categories c ON (u.category = c.id) ORDER BY s.id DESC LIMIT ?, ?');
	$stmt->bind_param('ii', $offset, $limit);
}

$stmt->execute();
$stmt->bind_result($date, $color, $user_id, $user_name, $message);
$data = array();
$patterns = array('pics/nb/smilies/', 'images/smilies/', 'images/nb/smilies/', 'images/ob/smilies', 'pics/ob/smilies');
$replacements = array('http://www.informatik-forum.at/pics/nb/smilies/', 'http://www.informatik-forum.at/images/smilies/', 'http://www.informatik-forum.at/images/nb/smilies/', 'http://www.informatik-forum.at/images/ob/smilies', 'http://www.informatik-forum.at/pics/ob/smilies');
while($stmt->fetch()) {
	// TODO DST problem
	$date = date('[d-m H:i]', $date+3600);
	$color = ($color == '-') ? 'user' : $color;
	$link = '?user=' . urlencode($user_name) . "&amp;limit=$limit";
	if(isset($_GET['text']) && trim($_GET['text']) != '') {
		$link .= '&amp;text=' . urlencode($_GET['text']);
	}
	$message = str_replace($patterns, $replacements, $message);
	$message = str_replace('/http:', 'http:', $message);
	$message = str_replace(' target="_blank"', '', $message);
	$message = str_replace(' border="0"', '', $message);

	// TODO problems with <embed> tag?
	$message = str_replace('width=&quot;200&quot; height=&quot;300&quot;', 'width="200" height="300"', $message);
	$data[] = array('date' => $date, 'color' => $color, 'user_id' => $user_id, 'user_name' => $user_name, 'message' => $message, 'user_link' => $link);
}
$stmt->close();

if(isset($_GET['text']) && trim($_GET['text']) != '') {
	$text = '%' . $_GET['text'] . '%';
	if(isset($_GET['user']) && trim($_GET['user']) != '') {
		$stmt = $mysqli->prepare('SELECT COUNT(*) shouts FROM shouts s JOIN users u ON (s.user = u.id) WHERE message LIKE ? AND u.name = ?');
		$stmt->bind_param('ss', $text, $_GET['user']);
	}
	else {
		$stmt = $mysqli->prepare('SELECT COUNT(*) shouts FROM shouts WHERE message LIKE ?');
		$stmt->bind_param('s', $text);
	}
}
else if(isset($_GET['user']) && trim($_GET['user']) != '') {
	$stmt = $mysqli->prepare('SELECT COUNT(*) shouts FROM shouts s JOIN users u ON (s.user = u.id) WHERE u.name = ?');
	$stmt->bind_param('s', $_GET['user']);
}
else {
	$stmt = $mysqli->prepare('SELECT COUNT(*) shouts FROM shouts');
}
$stmt->execute();
$stmt->bind_result($total_shouts);
$stmt->fetch();
$stmt->close();

$page_count = ceil($total_shouts/$limit);
$link_parts = "?limit=$limit";
if(isset($_GET['text']) && trim($_GET['text']) != '') {
	$link_parts .= '&amp;text=' . urlencode($_GET['text']);
}
if(isset($_GET['user']) && trim($_GET['user']) != '') {
	$link_parts .= '&amp;user=' . urlencode($_GET['user']);
}
$previous_page = $page-1;
if($previous_page < 0) {
	$previous_page = 1;
}
if($previous_page > $page_count) {
	$previous_page = $page_count;
}
$next_page = $page+1;
if($next_page < 0) {
	$next_page = 1;
}
if($next_page > $page_count) {
	$next_page = $page_count;
}
$previous_link = "$link_parts&amp;page=$previous_page";
$next_link = "$link_parts&amp;page=$next_page";

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
	a:hover { color: red; }
	a.user { color: #417394; }
	a.purple { color: purple; font-weight: bold; }
	a.green { color: green; font-weight: bold; }
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
		<tr><td>Page:</td><td><input type="text" name="page" value="<?php echo $page; ?>" /> (of <?php echo $page_count; ?>) <a href="<?php echo $previous_link ?>">Previous</a> <a href="<?php echo $next_link ?>">Next</a></td></tr>
		<tr><td></td><td><input type="submit" value="Filter" /><input type="button" value="Reset" onclick="document.location.href='?';" /></td></tr>
		</table>
		</form>
		</fieldset>
		<table>
			<?php foreach($data as $row): ?>
				<tr>
					<td class="date"><?php echo $row['date'] ?></td>
					<td class="user"><a class="<?php echo $row['color'] ?>" href="<?php echo $row['user_link'] ?>"><?php echo $row['user_name'] ?></a></td>
					<td class="message"><?php echo $row['message'] ?></td>
				</tr>
			<?php endforeach; ?>
		</table>		
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
