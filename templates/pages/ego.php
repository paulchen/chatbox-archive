<?php
echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<!-- TODO HTML tags inside page title -->
	<title>Chatbox users' ego sizes</title>
	<style type="text/css">
	body { font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif; font-size: 13px; }
	table { border: none; }
	td.date, td.user { white-space: nowrap; }
	a { text-decoration: none; color: #417394; }
	h1 > a { color: black; }
	a:hover { color: red; }
	a.user { color: #417394; }
	a.purple { color: purple; font-weight: bold; }
	a.green { color: green; font-weight: bold; }
	img { border: none; }
	th.left, td.left { text-align: left; }
	th.right, td.right { text-align: right; }
	</style>
</head>
<body>
	<h1><a href="ego.php">Chatbox users' ego sizes</a></h1>
	<div>
		<a href="./">Chatbox archive</a>
	</div>
	<hr />
	<div>
		<ul>
			<?php foreach($user_egos as $id => $ego): ?>
				<li><a class="<?php echo $users[$id]['color'] ?>" href="./?text=ego&amp;user=<?php echo htmlentities($users[$id]['name'], ENT_QUOTES, 'UTF-8') ?>&amp;limit=100&amp;page=1&amp;date=&amp;refresh=on"><?php echo htmlentities($users[$id]['name'], ENT_QUOTES, 'UTF-8'); ?></a>: <?php echo $ego ?></li>	
			<?php endforeach; ?>
		</ul>
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="images/xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
