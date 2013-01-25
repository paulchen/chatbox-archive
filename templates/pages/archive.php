<?php
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