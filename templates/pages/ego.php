<?php
echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<!-- TODO HTML tags inside page title -->
	<title>Ego points</title>
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
	<h1><a href="ego.php">Ego points</a></h1>
	<div>
		<a href="./">Chatbox archive</a>
	</div>
	<hr />
	<div>
		<h3>Rules</h3>
		<ul>
			<li>The smiley <img src="images/smilies/hail.gif" alt=":hail:" title=":hail:" /> (<b>:hail:</b>) makes one ego point available, <img src="images/smilies/multihail.gif" alt=":multihail:" title=":multihail:" /> (<b>:multihail:</b>) and <img src="images/smilies/multihaildb.gif" alt=":multihaildb:" title=":multihaildb:" /> (<b>:multihaildb:</b>) each make 16 ego points available.</li>
			<li>The smiley <img src="images/smilies/antihail.png" alt=":nohail:" title=":nohail:" /> (<b>:nohail:</b>) decrements the number of available ego points by <i>1</i> if there is at least one ego point available.</li>
			<li>Every user has a certain amount of ego points. The current ego points for each user are shown below. Users that have never increased or decreased their ego points are excluded from the list as they each have <i>0</i> ego points.</li>
			<li>By writing <b>ego++</b>, a user may increment his ego points by <i>1</i>; she may decrement her ego points by <i>1</i> by writing <b>ego--</b>.</li>
			<li>By writing <b>ego+=<i>n</i></b>, a user may increment his ego points by <i>n</i>; she may decrement her ego points by <i>n</i> by writing <b>ego-=<i>n</i></b>.</li>
			<li>Incrementing a user's ego points consumes an appropriate number of available ego points. A user may not consume ego points that have been made available by herself. In case there are not enough ego points available, the user's ego points will only be increased by the number of ego points that may be consumed by this user.</li>
			<li>Decrementing a user's ego points does not affect the number of available ego points. The size of the decrement of ego points is limited by <i>1000</i>.</li>
			<li>The number of available ego points is shown at the bottom of this page.</li>
		</ul>
	</div>
	<div>
		<h3>Current ego points</h3>
		<ul>
			<?php foreach($user_egos as $id => $ego): ?>
				<li><a class="<?php echo $users[$id]['color'] ?>" href="./?text=ego&amp;user=<?php echo urlencode($users[$id]['name']) ?>&amp;limit=100&amp;page=1&amp;date=&amp;refresh=on"><?php echo $users[$id]['name']; ?></a>: <?php echo $ego ?></li>	
			<?php endforeach; ?>
		</ul>
		Currently available ego points: <?php echo $available_ego ?>
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="images/xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
