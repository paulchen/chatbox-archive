<?php
echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dth">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Changelog</title>
	<!-- TODO move this to separate CSS file -->
	<style type="text/css">
	body { font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif; font-size: 13px; }
	table { border: none; }
	th.date, td.user { white-space: nowrap; }
	a { text-decoration: none; color: #417394; }
	h1 > a { color: black; }
	a:hover { color: red; }
	a.user { color: #417394; }
	a.purple { color: purple; font-weight: bold; }
	a.green { color: green; font-weight: bold; }
	img { border: none; }
	th.left, th.left { text-align: left; }
	th, td { text-align: right; }
	td:first-child, th:first-child { text-align: left; }
	th.center { text-align: center; }
	div.changelog > div { margin-left: 50px; margin-top: 1em; }
	div.changelog > div > div { font-weight: bold; margin-left: -50px; }
	</style>
</head>
<body>
	<h1><a href="banana.php">Changelog</a></h1>
	<div>
		<a href="./">Chatbox archive</a>
	</div>
	<hr />
	<div class="changelog">
		<div><div>2014-11-05</div>Introduced <a href="changelog.php">changelog page</a></div>
		<div><div>2014-11-04</div>Show number of unread messages in title of inactive windows/tabs</div>
		<div><div>2014-10-15</div>Fixed a set of Unicode characters. Now another set of Unicode characters is broken. Hooray.</div>
		<div><div>2014-10-13</div>Improved performance of database queries, reduced load time for AJAX requests to less than 100ms</div>
		<div><div>2014-06-24</div>Introduced version 2 of seen.php API</div>
		<div><div>2014-05-01</div>Fixed enormous memory consumption that broke the whole page occassionally</div>
		<div><div>2014-01-07</div>Introduced HTTP proxy for images in messages in order to resolve issues regarding non-HTTPS content within an HTTPS page</div>
		<div><div>2013-12-22</div>Introduced seen.php API for &quot;Ravu's Bot&quot;</div>
		<div><div>2013-11-26</div>Added <a href="banana.php">devil banana page</a></div>
		<div><div>2013-11-09</div>Added <a href="ego.php">ego count page</a></div>
		<div style="margin-left: 0px;"><span style="font-style: italic;">(changes before 2013-11-09 not listed here)</span></div>
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="images/xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
